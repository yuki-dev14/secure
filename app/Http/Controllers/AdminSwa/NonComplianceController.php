<?php

namespace App\Http\Controllers\AdminSwa;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\FamilyMember;
use App\Models\NonComplianceRecord;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NonComplianceController extends Controller
{
    /**
     * List all non-compliance records with filters.
     */
    public function index(Request $request): Response
    {
        $query = NonComplianceRecord::with(['beneficiary', 'familyMember', 'processor']);

        // ── Filters ──────────────────────────────────────────────────────────
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('beneficiary', fn($q) =>
                $q->where('unique_id', 'ilike', "%{$s}%")
                  ->orWhere('first_name', 'ilike', "%{$s}%")
                  ->orWhere('last_name', 'ilike', "%{$s}%")
            );
        }

        if ($request->filled('period'))   $query->where('period', $request->period);
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('source'))   $query->where('source', $request->source);

        if ($request->filled('barangay')) {
            $query->whereHas('beneficiary', fn($q) =>
                $q->where('barangay', $request->barangay)
            );
        }

        $records = $query->latest()->paginate(20)->withQueryString();

        // ── Supporting data ──────────────────────────────────────────────────
        $barangays = Beneficiary::active()->distinct()->pluck('barangay')->sort()->values();
        $periods   = $this->getAvailablePeriods();

        // ── Summary counts for current filters ───────────────────────────────
        $summaryQuery = NonComplianceRecord::query();
        if ($request->filled('period')) $summaryQuery->where('period', $request->period);

        $summary = [
            'total'     => $summaryQuery->count(),
            'pending'   => (clone $summaryQuery)->where('status', 'pending')->count(),
            'confirmed' => (clone $summaryQuery)->where('status', 'confirmed')->count(),
            'dismissed' => (clone $summaryQuery)->where('status', 'dismissed')->count(),
        ];

        return Inertia::render('AdminSwa/NonCompliance/Index', [
            'records'   => $records,
            'barangays' => $barangays,
            'periods'   => $periods,
            'summary'   => $summary,
            'filters'   => $request->only(['search', 'period', 'category', 'status', 'source', 'barangay']),
        ]);
    }

    /**
     * Show form to create a manual non-compliance entry.
     * Provides beneficiary list filtered by barangay/school.
     */
    public function create(Request $request): Response
    {
        // Pre-load beneficiaries for the selector
        $query = Beneficiary::with(['familyMembers'])->active();

        if ($request->filled('barangay')) {
            $query->where('barangay', $request->barangay);
        }

        $beneficiaries = $query->orderBy('last_name')->get()->map(fn($b) => [
            'id'            => $b->id,
            'unique_id'     => $b->unique_id,
            'full_name'     => $b->full_name,
            'barangay'      => $b->barangay,
            'family_members' => $b->familyMembers->map(fn($fm) => [
                'id'              => $fm->id,
                'full_name'       => "{$fm->first_name} {$fm->last_name}",
                'relationship'    => $fm->relationship,
                'is_school_age'   => $fm->is_school_age,
                'is_under_five'   => $fm->is_under_five,
                'is_pregnant'     => $fm->is_pregnant,
                'education_level' => $fm->education_level,
                'age'             => $fm->birthdate?->age,
            ]),
        ]);

        $barangays = Beneficiary::active()->distinct()->pluck('barangay')->sort()->values();
        $periods   = $this->getAvailablePeriods();

        return Inertia::render('AdminSwa/NonCompliance/Create', [
            'beneficiaries' => $beneficiaries,
            'barangays'     => $barangays,
            'periods'       => $periods,
        ]);
    }

    /**
     * Store a new non-compliance record (manual entry by school rep / midwife).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'beneficiary_id'        => 'required|exists:beneficiaries,id',
            'family_member_id'      => 'nullable|exists:family_members,id',
            'category'              => 'required|in:education,health',
            'source'                => 'required|in:school_rep,midwife',
            'reporter_name'         => 'nullable|string|max:200',
            'reporter_institution'  => 'nullable|string|max:200',
            'reason'                => 'required|string|max:500',
            'details'               => 'nullable|string',
            'period'                => 'required|string|max:20',
            'grant_affected'        => 'required|in:health_grant,education_elementary,education_junior_high,education_senior_high,rice_subsidy',
        ]);

        // Resolve period dates
        $periodData = $this->resolvePeriodDates($validated['period']);

        $record = NonComplianceRecord::create(array_merge($validated, [
            'period_start' => $periodData['start'],
            'period_end'   => $periodData['end'],
            'status'       => 'pending',
        ]));

        AuditLogService::log('non_compliance_created', $record, [], $record->toArray(),
            "Non-compliance flagged for beneficiary #{$record->beneficiary_id} — {$validated['category']}");

        return redirect()->route('adminswa.non-compliance.index')
            ->with('success', 'Non-compliance record created successfully. Awaiting review.');
    }

    /**
     * Confirm a pending non-compliance record (Admin SWA marks as confirmed).
     * This zeros out the relevant grant component for the period.
     */
    public function confirm(Request $request, NonComplianceRecord $record): RedirectResponse
    {
        $request->validate([
            'processing_notes' => 'nullable|string|max:500',
        ]);

        $old = $record->toArray();

        $record->update([
            'status'           => 'confirmed',
            'processed_by'     => auth()->id(),
            'processed_at'     => now(),
            'processing_notes' => $request->processing_notes,
        ]);

        // Update beneficiary compliance status
        $this->recomputeBeneficiaryCompliance($record->beneficiary_id, $record->period);

        AuditLogService::log('non_compliance_confirmed', $record, $old, $record->fresh()->toArray(),
            "Non-compliance confirmed for beneficiary #{$record->beneficiary_id}");

        return back()->with('success', 'Non-compliance record confirmed. Grant component will be zeroed out.');
    }

    /**
     * Dismiss a pending non-compliance record (Admin SWA determines it's invalid).
     */
    public function dismiss(Request $request, NonComplianceRecord $record): RedirectResponse
    {
        $request->validate([
            'processing_notes' => 'required|string|max:500',
        ]);

        $old = $record->toArray();

        $record->update([
            'status'           => 'dismissed',
            'processed_by'     => auth()->id(),
            'processed_at'     => now(),
            'processing_notes' => $request->processing_notes,
        ]);

        AuditLogService::log('non_compliance_dismissed', $record, $old, $record->fresh()->toArray(),
            "Non-compliance dismissed for beneficiary #{$record->beneficiary_id}");

        return back()->with('success', 'Non-compliance record dismissed.');
    }

    /**
     * Batch confirm/dismiss multiple records at once.
     */
    public function batchProcess(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'record_ids'       => 'required|array|min:1',
            'record_ids.*'     => 'integer|exists:non_compliance_records,id',
            'action'           => 'required|in:confirm,dismiss',
            'processing_notes' => 'nullable|string|max:500',
        ]);

        $records = NonComplianceRecord::whereIn('id', $validated['record_ids'])
            ->where('status', 'pending')
            ->get();

        $processed = 0;
        foreach ($records as $record) {
            $record->update([
                'status'           => $validated['action'] === 'confirm' ? 'confirmed' : 'dismissed',
                'processed_by'     => auth()->id(),
                'processed_at'     => now(),
                'processing_notes' => $validated['processing_notes'] ?? "Batch {$validated['action']}ed",
            ]);
            $processed++;

            if ($validated['action'] === 'confirm') {
                $this->recomputeBeneficiaryCompliance($record->beneficiary_id, $record->period);
            }
        }

        return back()->with('success', "{$processed} records {$validated['action']}ed successfully.");
    }

    /**
     * Show import page for Google Forms / Excel uploads.
     */
    public function importForm(): Response
    {
        $periods   = $this->getAvailablePeriods();
        $barangays = Beneficiary::active()->distinct()->pluck('barangay')->sort()->values();

        return Inertia::render('AdminSwa/NonCompliance/Import', [
            'periods'   => $periods,
            'barangays' => $barangays,
        ]);
    }

    /**
     * Process uploaded Excel/CSV file containing non-compliance flags.
     */
    public function import(Request $request): RedirectResponse
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
            'source'   => 'required|in:school_rep,midwife',
            'reporter_name'        => 'nullable|string|max:200',
            'reporter_institution' => 'nullable|string|max:200',
        ], [
            'file.required' => 'Please select a CSV or Excel file to upload.',
            'file.max'      => 'The file size must not exceed 10 MB.',
        ]);

        $batchId    = 'IMPORT-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $periodData = $this->resolvePeriodDates($request->period);

        $import = new \App\Imports\NonComplianceImport(
            period: $request->period,
            periodStart: $periodData['start'],
            periodEnd: $periodData['end'],
            category: $request->category,
            source: $request->source,
            reporterName: $request->reporter_name,
            reporterInstitution: $request->reporter_institution,
            importBatchId: $batchId,
        );

        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

        $imported = $import->getImportedCount();
        $skipped  = $import->getSkippedCount();

        AuditLogService::log('non_compliance_imported', null, [], [
            'batch_id' => $batchId,
            'imported' => $imported,
            'skipped'  => $skipped,
        ], "Bulk non-compliance import: {$imported} records imported, {$skipped} skipped (Batch: {$batchId})");

        return redirect()->route('adminswa.non-compliance.index')
            ->with('success', "Import complete! {$imported} non-compliance records imported, {$skipped} skipped.");
    }

    /**
     * Download import template for non-compliance records.
     */
    public function importTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Non-Compliance Records');

        $headers = [
            'A' => 'beneficiary_unique_id',
            'B' => 'family_member_name',
            'C' => 'reason',
            'D' => 'details',
            'E' => 'grant_affected',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        // Styling
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
        ]);

        foreach (['A', 'B', 'C', 'D', 'E'] as $c) {
            $sheet->getColumnDimension($c)->setWidth(25);
        }

        // Sample row
        $sheet->setCellValue('A2', '4PS-LPA-000001');
        $sheet->setCellValue('B2', 'Juan Dela Cruz');
        $sheet->setCellValue('C2', 'Attendance below 85%');
        $sheet->setCellValue('D2', 'Missed 12 school days in Jan-Feb');
        $sheet->setCellValue('E2', 'education_elementary');

        $sheet->getStyle('A2:E2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
        ]);

        // Instructions sheet
        $notes = $spreadsheet->createSheet();
        $notes->setTitle('Instructions');
        $noteRows = [
            ['COLUMN', 'REQUIRED', 'VALUES', 'NOTES'],
            ['beneficiary_unique_id', 'YES', 'e.g. 4PS-LPA-000001', 'Must match existing beneficiary in the system.'],
            ['family_member_name', 'No', 'First Last', 'Optional — name match against family members.'],
            ['reason', 'YES', 'Text', 'Non-compliance reason: e.g. "Attendance below 85%", "Missed deworming"'],
            ['details', 'No', 'Text', 'Additional details.'],
            ['grant_affected', 'YES', 'health_grant | education_elementary | education_junior_high | education_senior_high | rice_subsidy', 'Which grant component to zero out.'],
            ['', '', '', ''],
            ['NOTES:', '', '', ''],
            ['- Period, category, and source are set during upload (not per-row).', '', '', ''],
            ['- Row 2 is a sample. Delete or replace it.', '', '', ''],
            ['- Duplicate entries (same beneficiary + category + period) will be skipped.', '', '', ''],
        ];

        foreach ($noteRows as $ri => $row) {
            $notes->fromArray($row, null, 'A' . ($ri + 1));
        }
        $notes->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
        ]);
        foreach (['A', 'B', 'C', 'D'] as $c) {
            $notes->getColumnDimension($c)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'SECURE-4Ps-NonCompliance-Import-Template.xlsx';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Recompute the beneficiary's is_compliant flag based on confirmed non-compliance records.
     */
    private function recomputeBeneficiaryCompliance(int $beneficiaryId, string $period): void
    {
        $hasConfirmedNC = NonComplianceRecord::where('beneficiary_id', $beneficiaryId)
            ->where('period', $period)
            ->where('status', 'confirmed')
            ->exists();

        Beneficiary::where('id', $beneficiaryId)->update([
            'is_compliant'          => !$hasConfirmedNC,
            'last_compliance_check' => now(),
        ]);
    }

    private function resolvePeriodDates(string $periodValue): array
    {
        $periods = $this->getAvailablePeriods();
        foreach ($periods as $p) {
            if ($p['value'] === $periodValue) {
                return ['start' => $p['start'], 'end' => $p['end']];
            }
        }
        // Fallback
        return ['start' => now()->startOfMonth()->toDateString(), 'end' => now()->endOfMonth()->toDateString()];
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
