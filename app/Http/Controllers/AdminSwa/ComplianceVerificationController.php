<?php

namespace App\Http\Controllers\AdminSwa;

use App\Http\Controllers\Controller;
use App\Imports\ComplianceVerificationImport;
use App\Mail\ComplianceVerificationMail;
use App\Models\Beneficiary;
use App\Models\ComplianceVerificationBatch;
use App\Models\FamilyMember;
use App\Models\NonComplianceRecord;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ComplianceVerificationController extends Controller
{
    /**
     * Main page: send verification lists + import results + history.
     */
    public function index(Request $request): Response
    {
        $periods = $this->getAvailablePeriods();
        $currentPeriod = $this->getCurrentPeriod();

        // Count of education-eligible beneficiaries (have school-age children)
        $eduCount = Beneficiary::active()
            ->whereHas('familyMembers', fn($q) => $q->where('is_school_age', true))
            ->count();

        // Count of health-eligible beneficiaries (have under-5 children OR pregnant members)
        $healthCount = Beneficiary::active()
            ->whereHas('familyMembers', fn($q) =>
                $q->where('is_under_five', true)->orWhere('is_pregnant', true)
            )
            ->count();

        // Verification history
        $historyQuery = ComplianceVerificationBatch::with(['sender', 'importer'])
            ->latest('sent_at');

        if ($request->filled('history_period')) {
            $historyQuery->where('period', $request->history_period);
        }
        if ($request->filled('history_category')) {
            $historyQuery->where('category', $request->history_category);
        }

        $history = $historyQuery->paginate(15)->withQueryString();

        // NC summary for current period
        $period = $request->get('period', $currentPeriod['value']);
        $ncSummary = [
            'education_nc' => NonComplianceRecord::where('period', $period)
                ->where('category', 'education')->where('status', 'confirmed')->count(),
            'health_nc'    => NonComplianceRecord::where('period', $period)
                ->where('category', 'health')->where('status', 'confirmed')->count(),
            'total_nc'     => NonComplianceRecord::where('period', $period)
                ->where('status', 'confirmed')->count(),
        ];

        return Inertia::render('AdminSwa/ComplianceVerification/Index', [
            'periods'       => $periods,
            'currentPeriod' => $currentPeriod,
            'eduCount'      => $eduCount,
            'healthCount'   => $healthCount,
            'history'       => $history,
            'ncSummary'     => $ncSummary,
            'filters'       => $request->only(['history_period', 'history_category', 'period']),
        ]);
    }

    /**
     * Generate Excel with beneficiary list and send to recipient email.
     */
    public function generateAndSend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period'          => 'required|string|max:20',
            'category'        => 'required|in:education,health',
            'recipient_email' => 'required|email|max:200',
            'recipient_name'  => 'nullable|string|max:200',
        ]);

        $periodData  = $this->resolvePeriodDates($validated['period']);
        $periodLabel = $this->getPeriodLabel($validated['period']);

        // Generate the Excel
        $filePath = $this->generateVerificationExcel(
            $validated['category'],
            $validated['period'],
            $periodLabel,
        );

        // Count beneficiaries included
        $beneficiaryCount = $this->countBeneficiariesForCategory($validated['category']);

        // Send email
        Mail::to($validated['recipient_email'])
            ->send(new ComplianceVerificationMail(
                category: $validated['category'],
                periodLabel: $periodLabel,
                recipientName: $validated['recipient_name'] ?? 'Verifier',
                beneficiaryCount: $beneficiaryCount,
                filePath: $filePath,
            ));

        // Create batch record
        $batch = ComplianceVerificationBatch::create([
            'period'            => $validated['period'],
            'category'          => $validated['category'],
            'recipient_email'   => $validated['recipient_email'],
            'recipient_name'    => $validated['recipient_name'],
            'beneficiary_count' => $beneficiaryCount,
            'sent_by'           => auth()->id(),
            'sent_at'           => now(),
            'status'            => 'sent',
            'file_path'         => $filePath,
        ]);

        $categoryDisplay = $validated['category'] === 'education' ? 'Education' : 'Health';

        AuditLogService::log('compliance_verification_sent', $batch, [], $batch->toArray(),
            "Compliance verification list ({$categoryDisplay}) sent to {$validated['recipient_email']} for period {$periodLabel}");

        return redirect()->route('adminswa.compliance-verification.index')
            ->with('success', "{$categoryDisplay} compliance verification list sent to {$validated['recipient_email']} ({$beneficiaryCount} beneficiaries).");
    }

    /**
     * Download the template Excel directly (without emailing).
     */
    public function downloadTemplate(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $request->validate([
            'category' => 'required|in:education,health',
            'period'   => 'required|string|max:20',
        ]);

        $periodLabel = $this->getPeriodLabel($request->period);
        $filePath    = $this->generateVerificationExcel(
            $request->category,
            $request->period,
            $periodLabel,
        );

        $categoryDisplay = $request->category === 'education' ? 'Education' : 'Health';
        $filename = "SECURE-4Ps-{$categoryDisplay}-Verification-{$periodLabel}.xlsx";

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Import the returned Excel with non-compliance flags.
     */
    public function importResults(Request $request): RedirectResponse
    {
        $request->validate([
            'file'     => ['required', 'file', 'max:10240', function ($attribute, $value, $fail) {
                $ext = strtolower($value->getClientOriginalExtension());
                if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
                    $fail('The uploaded file must be a valid Excel (.xlsx, .xls) or CSV (.csv) file.');
                }
            }],
            'period'   => 'required|string|max:20',
            'category' => 'required|in:education,health',
            'batch_id' => 'nullable|integer|exists:compliance_verification_batches,id',
        ], [
            'file.required' => 'Please select a CSV or Excel file to upload.',
            'file.max'      => 'The file size must not exceed 10 MB.',
        ]);

        $periodData = $this->resolvePeriodDates($request->period);
        $batchId    = 'CVIMPORT-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $source     = $request->category === 'education' ? 'school_rep' : 'midwife';

        $import = new ComplianceVerificationImport(
            period: $request->period,
            periodStart: $periodData['start'],
            periodEnd: $periodData['end'],
            category: $request->category,
            source: $source,
            importBatchId: $batchId,
        );

        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

        $imported  = $import->getImportedCount();
        $skipped   = $import->getSkippedCount();
        $compliant = $import->getCompliantCount();

        // Update the verification batch if linked
        if ($request->batch_id) {
            ComplianceVerificationBatch::where('id', $request->batch_id)->update([
                'status'              => 'imported',
                'imported_by'         => auth()->id(),
                'imported_at'         => now(),
                'non_compliant_count' => $imported,
            ]);
        } else {
            // Create a standalone import batch record
            ComplianceVerificationBatch::create([
                'period'              => $request->period,
                'category'            => $request->category,
                'recipient_email'     => 'direct-import',
                'beneficiary_count'   => $compliant + $imported + $skipped,
                'non_compliant_count' => $imported,
                'sent_by'             => auth()->id(),
                'sent_at'             => now(),
                'imported_by'         => auth()->id(),
                'imported_at'         => now(),
                'status'              => 'imported',
            ]);
        }

        $categoryDisplay = $request->category === 'education' ? 'Education' : 'Health';

        AuditLogService::log('compliance_verification_imported', null, [], [
            'batch_id'       => $batchId,
            'category'       => $request->category,
            'period'         => $request->period,
            'imported'       => $imported,
            'skipped'        => $skipped,
            'compliant'      => $compliant,
        ], "Compliance verification import ({$categoryDisplay}): {$imported} non-compliant, {$compliant} compliant, {$skipped} skipped");

        return redirect()->route('adminswa.compliance-verification.index')
            ->with('success', "Import complete! {$imported} beneficiaries flagged as non-compliant, {$compliant} remained compliant, {$skipped} skipped.");
    }

    /**
     * Generate a summary report Excel of non-compliant beneficiaries for Superadmin.
     */
    public function reportForSuperadmin(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $request->validate([
            'period'   => 'required|string|max:20',
            'category' => 'required|in:education,health',
        ]);

        $periodLabel     = $this->getPeriodLabel($request->period);
        $categoryDisplay = $request->category === 'education' ? 'Education' : 'Health';

        $records = NonComplianceRecord::with(['beneficiary', 'familyMember', 'processor'])
            ->where('period', $request->period)
            ->where('category', $request->category)
            ->where('status', 'confirmed')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("NC Report - {$categoryDisplay}");

        // Header row
        $headers = [
            'A' => 'Beneficiary ID',
            'B' => 'Beneficiary Name',
            'C' => 'Barangay',
            'D' => 'Family Member',
            'E' => 'Reason',
            'F' => 'Grant Affected',
            'G' => 'Source',
            'H' => 'Status',
            'I' => 'Confirmed By',
            'J' => 'Confirmed At',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        // Header styling
        $lastCol = 'J';
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC2626']],
        ]);

        // Data rows
        $row = 2;
        foreach ($records as $record) {
            $sheet->setCellValue("A{$row}", $record->beneficiary?->unique_id ?? '');
            $sheet->setCellValue("B{$row}", $record->beneficiary?->full_name ?? '');
            $sheet->setCellValue("C{$row}", $record->beneficiary?->barangay ?? '');
            $sheet->setCellValue("D{$row}", $record->familyMember
                ? "{$record->familyMember->first_name} {$record->familyMember->last_name}"
                : '—');
            $sheet->setCellValue("E{$row}", $record->reason);
            $sheet->setCellValue("F{$row}", $this->grantLabel($record->grant_affected));
            $sheet->setCellValue("G{$row}", $record->source_display);
            $sheet->setCellValue("H{$row}", strtoupper($record->status));
            $sheet->setCellValue("I{$row}", $record->processor?->name ?? '—');
            $sheet->setCellValue("J{$row}", $record->processed_at?->format('M d, Y g:i A') ?? '—');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Summary sheet
        $summary = $spreadsheet->createSheet();
        $summary->setTitle('Summary');

        $summary->setCellValue('A1', 'SECURE 4Ps — Non-Compliance Report');
        $summary->setCellValue('A2', "Category: {$categoryDisplay}");
        $summary->setCellValue('A3', "Period: {$periodLabel}");
        $summary->setCellValue('A4', "Generated: " . now()->format('F d, Y g:i A'));
        $summary->setCellValue('A5', "Generated By: " . auth()->user()->name);
        $summary->setCellValue('A7', 'Total Non-Compliant:');
        $summary->setCellValue('B7', $records->count());

        // Group by barangay
        $byBarangay = $records->groupBy(fn($r) => $r->beneficiary?->barangay ?? 'Unknown');
        $row = 9;
        $summary->setCellValue("A{$row}", 'Barangay');
        $summary->setCellValue("B{$row}", 'NC Count');
        $summary->getStyle("A{$row}:B{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
        ]);
        $row++;
        foreach ($byBarangay->sortKeys() as $barangay => $recs) {
            $summary->setCellValue("A{$row}", $barangay);
            $summary->setCellValue("B{$row}", $recs->count());
            $row++;
        }

        foreach (['A', 'B'] as $c) {
            $summary->getColumnDimension($c)->setAutoSize(true);
        }

        // Style title
        $summary->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E3A5F']],
        ]);

        $spreadsheet->setActiveSheetIndex(0);

        $writer   = new Xlsx($spreadsheet);
        $filename = "SECURE-4Ps-NC-Report-{$categoryDisplay}-{$periodLabel}.xlsx";
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }

    // ─── Excel Generation ───────────────────────────────────────────────────────

    private function generateVerificationExcel(string $category, string $period, string $periodLabel): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($category === 'education') {
            $sheet->setTitle('Education Verification');
            $this->buildEducationSheet($sheet, $period);
        } else {
            $sheet->setTitle('Health Verification');
            $this->buildHealthSheet($sheet, $period);
        }

        // Instructions sheet
        $this->addInstructionsSheet($spreadsheet, $category);

        $spreadsheet->setActiveSheetIndex(0);

        $categoryDisplay = $category === 'education' ? 'Education' : 'Health';
        $filename = "SECURE-4Ps-{$categoryDisplay}-Verification-{$periodLabel}.xlsx";
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return $tmpPath;
    }

    private function buildEducationSheet(mixed $sheet, string $period): void
    {
        $headers = [
            'A' => 'beneficiary_unique_id',
            'B' => 'beneficiary_name',
            'C' => 'barangay',
            'D' => 'family_member_name',
            'E' => 'education_level',
            'F' => 'school_name',
            'G' => 'compliance_status',
            'H' => 'reason',
            'I' => 'details',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        // Header styling
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
        ]);

        // Fetch beneficiaries with school-age children
        $beneficiaries = Beneficiary::with(['familyMembers' => fn($q) =>
            $q->where('is_school_age', true)->where('is_active', true)
        ])->active()
          ->whereHas('familyMembers', fn($q) => $q->where('is_school_age', true))
          ->orderBy('barangay')
          ->orderBy('last_name')
          ->get();

        $row = 2;
        foreach ($beneficiaries as $b) {
            foreach ($b->familyMembers as $fm) {
                $sheet->setCellValue("A{$row}", $b->unique_id);
                $sheet->setCellValue("B{$row}", $b->full_name);
                $sheet->setCellValue("C{$row}", $b->barangay);
                $sheet->setCellValue("D{$row}", "{$fm->first_name} {$fm->last_name}");
                $sheet->setCellValue("E{$row}", $this->educationLevelLabel($fm->education_level));
                $sheet->setCellValue("F{$row}", $fm->school_name ?? '');
                $sheet->setCellValue("G{$row}", 'COMPLIANT');
                $sheet->setCellValue("H{$row}", '');
                $sheet->setCellValue("I{$row}", '');
                $row++;
            }
        }

        // Style the compliance_status column with data validation
        if ($row > 2) {
            $validation = $sheet->getCell("G2")->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setFormula1('"COMPLIANT,NON_COMPLIANT"');
            $validation->setAllowBlank(false);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Invalid Status');
            $validation->setError('Please choose COMPLIANT or NON_COMPLIANT');

            // Apply to all data rows
            for ($r = 2; $r < $row; $r++) {
                $sheet->getCell("G{$r}")->setDataValidation(clone $validation);
            }

            // Color compliant cells green
            $sheet->getStyle("G2:G" . ($row - 1))->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '16A34A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
            ]);
        }

        // Auto-size columns
        foreach (range('A', 'I') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Protect non-editable columns (A-F: read-only)
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('secure4ps');

        // Unlock editable columns (G, H, I)
        if ($row > 2) {
            $sheet->getStyle("G2:I" . ($row - 1))->getProtection()
                ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
        }
    }

    private function buildHealthSheet(mixed $sheet, string $period): void
    {
        $headers = [
            'A' => 'beneficiary_unique_id',
            'B' => 'beneficiary_name',
            'C' => 'barangay',
            'D' => 'family_member_name',
            'E' => 'health_category',
            'F' => 'compliance_status',
            'G' => 'reason',
            'H' => 'details',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        // Header styling
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
        ]);

        // Fetch beneficiaries with under-5 or pregnant family members
        $beneficiaries = Beneficiary::with(['familyMembers' => fn($q) =>
            $q->where('is_active', true)
              ->where(fn($q2) => $q2->where('is_under_five', true)->orWhere('is_pregnant', true))
        ])->active()
          ->whereHas('familyMembers', fn($q) =>
              $q->where('is_under_five', true)->orWhere('is_pregnant', true)
          )
          ->orderBy('barangay')
          ->orderBy('last_name')
          ->get();

        $row = 2;
        foreach ($beneficiaries as $b) {
            foreach ($b->familyMembers as $fm) {
                $healthCategory = $fm->is_pregnant ? 'Pregnant' : 'Under 5';

                $sheet->setCellValue("A{$row}", $b->unique_id);
                $sheet->setCellValue("B{$row}", $b->full_name);
                $sheet->setCellValue("C{$row}", $b->barangay);
                $sheet->setCellValue("D{$row}", "{$fm->first_name} {$fm->last_name}");
                $sheet->setCellValue("E{$row}", $healthCategory);
                $sheet->setCellValue("F{$row}", 'COMPLIANT');
                $sheet->setCellValue("G{$row}", '');
                $sheet->setCellValue("H{$row}", '');
                $row++;
            }
        }

        // Data validation for compliance_status column
        if ($row > 2) {
            $validation = $sheet->getCell("F2")->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setFormula1('"COMPLIANT,NON_COMPLIANT"');
            $validation->setAllowBlank(false);
            $validation->setShowDropDown(true);

            for ($r = 2; $r < $row; $r++) {
                $sheet->getCell("F{$r}")->setDataValidation(clone $validation);
            }

            $sheet->getStyle("F2:F" . ($row - 1))->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '16A34A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
            ]);
        }

        // Auto-size columns
        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Protect non-editable columns (A-E)
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('secure4ps');

        if ($row > 2) {
            $sheet->getStyle("F2:H" . ($row - 1))->getProtection()
                ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
        }
    }

    private function addInstructionsSheet(Spreadsheet $spreadsheet, string $category): void
    {
        $notes = $spreadsheet->createSheet();
        $notes->setTitle('Instructions');

        $categoryDisplay = $category === 'education' ? 'Education' : 'Health & Nutrition';
        $verifierRole    = $category === 'education' ? 'School Representative' : 'Midwife';

        $rows = [
            ["SECURE 4Ps — {$categoryDisplay} Compliance Verification"],
            [''],
            ['HOW TO USE THIS FILE:'],
            ["1. Review the beneficiary list on the '{$categoryDisplay} Verification' sheet."],
            ['2. All beneficiaries are listed as COMPLIANT by default.'],
            ['3. For beneficiaries who DID NOT comply, change the "compliance_status" column to NON_COMPLIANT.'],
            ['4. Fill in the "reason" column for each non-compliant entry.'],
            ['5. Optionally add details in the "details" column.'],
            ['6. DO NOT modify columns A-' . ($category === 'education' ? 'F' : 'E') . ' (beneficiary info).'],
            ['7. Return the completed file to your DSWD contact.'],
            [''],
            ['IMPORTANT NOTES:'],
            ['- Only rows marked NON_COMPLIANT will be processed.'],
            ['- COMPLIANT rows will be ignored (they are already compliant by default).'],
            ['- Non-compliant beneficiaries will NOT receive their ' . ($category === 'education' ? 'education' : 'health') . ' grant for this period.'],
            [''],
            ['COLUMN DESCRIPTIONS:'],
        ];

        if ($category === 'education') {
            $rows[] = ['beneficiary_unique_id — System ID (DO NOT MODIFY)'];
            $rows[] = ['beneficiary_name — Full name of the household grantee (DO NOT MODIFY)'];
            $rows[] = ['barangay — Barangay (DO NOT MODIFY)'];
            $rows[] = ['family_member_name — Name of the school-age child (DO NOT MODIFY)'];
            $rows[] = ['education_level — Elementary / Junior High / Senior High (DO NOT MODIFY)'];
            $rows[] = ['school_name — Enrolled school (DO NOT MODIFY)'];
            $rows[] = ['compliance_status — COMPLIANT or NON_COMPLIANT (EDITABLE)'];
            $rows[] = ['reason — Reason for non-compliance, e.g. "Attendance below 85%" (EDITABLE)'];
            $rows[] = ['details — Additional details (EDITABLE)'];
        } else {
            $rows[] = ['beneficiary_unique_id — System ID (DO NOT MODIFY)'];
            $rows[] = ['beneficiary_name — Full name of the household grantee (DO NOT MODIFY)'];
            $rows[] = ['barangay — Barangay (DO NOT MODIFY)'];
            $rows[] = ['family_member_name — Name of the child/pregnant member (DO NOT MODIFY)'];
            $rows[] = ['health_category — Under 5 / Pregnant (DO NOT MODIFY)'];
            $rows[] = ['compliance_status — COMPLIANT or NON_COMPLIANT (EDITABLE)'];
            $rows[] = ['reason — Reason for non-compliance, e.g. "Missed immunization" (EDITABLE)'];
            $rows[] = ['details — Additional details (EDITABLE)'];
        }

        foreach ($rows as $ri => $row) {
            $notes->fromArray($row, null, 'A' . ($ri + 1));
        }

        $notes->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E3A5F']],
        ]);
        $notes->getStyle('A3')->applyFromArray(['font' => ['bold' => true, 'size' => 12]]);
        $notes->getStyle('A12')->applyFromArray(['font' => ['bold' => true, 'size' => 12]]);
        $notes->getStyle('A17')->applyFromArray(['font' => ['bold' => true, 'size' => 12]]);

        $notes->getColumnDimension('A')->setWidth(80);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    private function countBeneficiariesForCategory(string $category): int
    {
        if ($category === 'education') {
            return Beneficiary::active()
                ->whereHas('familyMembers', fn($q) => $q->where('is_school_age', true))
                ->count();
        }

        return Beneficiary::active()
            ->whereHas('familyMembers', fn($q) =>
                $q->where('is_under_five', true)->orWhere('is_pregnant', true)
            )
            ->count();
    }

    private function educationLevelLabel(string $level): string
    {
        return match ($level) {
            'elementary'  => 'Elementary',
            'junior_high' => 'Junior High School',
            'senior_high' => 'Senior High School',
            default       => ucfirst($level),
        };
    }

    private function grantLabel(string $grant): string
    {
        return match ($grant) {
            'health_grant'            => 'Health Grant (₱750/mo)',
            'education_elementary'    => 'Education – Elementary (₱300/mo)',
            'education_junior_high'   => 'Education – Junior High (₱500/mo)',
            'education_senior_high'   => 'Education – Senior High (₱700/mo)',
            'rice_subsidy'            => 'Rice Subsidy (₱600/mo)',
            default                   => $grant,
        };
    }

    private function resolvePeriodDates(string $periodValue): array
    {
        $periods = $this->getAvailablePeriods();
        foreach ($periods as $p) {
            if ($p['value'] === $periodValue) {
                return ['start' => $p['start'], 'end' => $p['end']];
            }
        }
        return ['start' => now()->startOfMonth()->toDateString(), 'end' => now()->endOfMonth()->toDateString()];
    }

    private function getPeriodLabel(string $periodValue): string
    {
        $periods = $this->getAvailablePeriods();
        foreach ($periods as $p) {
            if ($p['value'] === $periodValue) {
                return $p['label'];
            }
        }
        return $periodValue;
    }

    private function getCurrentPeriod(): array
    {
        $periods = $this->getAvailablePeriods();
        $today   = now()->toDateString();
        foreach ($periods as $p) {
            if ($today >= $p['start'] && $today <= $p['end']) return $p;
        }
        return $periods[0];
    }

    private function getAvailablePeriods(): array
    {
        $bimonthly = [
            ['p' => 1, 'label' => 'P1 (January–February)',   'start' => '01-01', 'end' => '02-28'],
            ['p' => 2, 'label' => 'P2 (March–April)',        'start' => '03-01', 'end' => '04-30'],
            ['p' => 3, 'label' => 'P3 (May–June)',           'start' => '05-01', 'end' => '06-30'],
            ['p' => 4, 'label' => 'P4 (July–August)',        'start' => '07-01', 'end' => '08-31'],
            ['p' => 5, 'label' => 'P5 (September–October)',  'start' => '09-01', 'end' => '10-31'],
            ['p' => 6, 'label' => 'P6 (November–December)',  'start' => '11-01', 'end' => '12-31'],
        ];

        $periods = [];
        $year    = now()->year;

        foreach ([$year, $year + 1] as $y) {
            foreach ($bimonthly as $p) {
                $end = $p['end'];
                if ($p['p'] === 1 && date('L', mktime(0, 0, 0, 1, 1, $y))) {
                    $end = '02-29';
                }
                $periods[] = [
                    'value' => "{$y}-P{$p['p']}",
                    'label' => "{$y} {$p['label']}",
                    'start' => "{$y}-{$p['start']}",
                    'end'   => "{$y}-{$end}",
                ];
            }
        }

        return $periods;
    }
}
