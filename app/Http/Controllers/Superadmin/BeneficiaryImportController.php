<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Imports\BeneficiaryImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class BeneficiaryImportController extends Controller
{
    /**
     * Show the import page.
     */
    public function index(): Response
    {
        return Inertia::render('Superadmin/Beneficiaries/Import');
    }

    /**
     * Handle the uploaded Excel file and import beneficiaries.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new BeneficiaryImport(auth()->id());
        Excel::import($import, $request->file('file'));

        $msg = "Import complete: {$import->successCount} beneficiaries imported.";
        if ($import->skipCount > 0) {
            $msg .= " {$import->skipCount} row(s) skipped.";
        }

        return redirect()
            ->route('superadmin.beneficiaries.import')
            ->with('success', $msg)
            ->with('skipped', $import->skipped);
    }

    /**
     * Download a pre-filled Excel template with the correct column structure.
     */
    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Beneficiaries');

        // ── Headers ──────────────────────────────────────────────────────────
        $headers = [
            // Beneficiary
            'A'  => 'listahanan_id',
            'B'  => 'first_name',
            'C'  => 'middle_name',
            'D'  => 'last_name',
            'E'  => 'suffix',
            'F'  => 'birthdate',
            'G'  => 'sex',
            'H'  => 'civil_status',
            'I'  => 'contact_number',
            'J'  => 'house_no',
            'K'  => 'street',
            'L'  => 'purok',
            'M'  => 'barangay',
            'N'  => 'enrollment_date',
            'O'  => 'remarks',
            // Member 1
            'P'  => 'member_1_first_name',
            'Q'  => 'member_1_middle_name',
            'R'  => 'member_1_last_name',
            'S'  => 'member_1_birthdate',
            'T'  => 'member_1_sex',
            'U'  => 'member_1_relationship',
            'V'  => 'member_1_education_level',
            'W'  => 'member_1_school_name',
            // Member 2
            'X'  => 'member_2_first_name',
            'Y'  => 'member_2_middle_name',
            'Z'  => 'member_2_last_name',
            'AA' => 'member_2_birthdate',
            'AB' => 'member_2_sex',
            'AC' => 'member_2_relationship',
            'AD' => 'member_2_education_level',
            'AE' => 'member_2_school_name',
            // Member 3
            'AF' => 'member_3_first_name',
            'AG' => 'member_3_middle_name',
            'AH' => 'member_3_last_name',
            'AI' => 'member_3_birthdate',
            'AJ' => 'member_3_sex',
            'AK' => 'member_3_relationship',
            'AL' => 'member_3_education_level',
            'AM' => 'member_3_school_name',
            // Member 4
            'AN' => 'member_4_first_name',
            'AO' => 'member_4_middle_name',
            'AP' => 'member_4_last_name',
            'AQ' => 'member_4_birthdate',
            'AR' => 'member_4_sex',
            'AS' => 'member_4_relationship',
            'AT' => 'member_4_education_level',
            'AU' => 'member_4_school_name',
            // Member 5
            'AV' => 'member_5_first_name',
            'AW' => 'member_5_middle_name',
            'AX' => 'member_5_last_name',
            'AY' => 'member_5_birthdate',
            'AZ' => 'member_5_sex',
            'BA' => 'member_5_relationship',
            'BB' => 'member_5_education_level',
            'BC' => 'member_5_school_name',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        // ── Header row styling ────────────────────────────────────────────────
        $lastCol = 'BC';
        $headerRange = "A1:{$lastCol}1";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);

        // Colour-code member columns differently
        $memberRange = "P1:{$lastCol}1";
        $sheet->getStyle($memberRange)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
        ]);

        // ── Column widths ─────────────────────────────────────────────────────
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setWidth(20);
        }
        // Member columns are narrower
        foreach ($headers as $col => $label) {
            if (str_starts_with($label, 'member_')) {
                $sheet->getColumnDimension($col)->setWidth(18);
            }
        }

        // ── Row height ────────────────────────────────────────────────────────
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Sample data row ───────────────────────────────────────────────────
        $sample = [
            'A'  => 'NHTS-PR-0001',
            'B'  => 'Maria',
            'C'  => 'Santos',
            'D'  => 'dela Cruz',
            'E'  => '',
            'F'  => '1990-06-15',
            'G'  => 'female',
            'H'  => 'married',
            'I'  => '09171234567',
            'J'  => '12',
            'K'  => 'Rizal St.',
            'L'  => '1-A',
            'M'  => 'Balintawak',
            'N'  => '2024-01-01',
            'O'  => '',
            // Member 1
            'P'  => 'Jose',
            'Q'  => '',
            'R'  => 'dela Cruz',
            'S'  => '2015-03-10',
            'T'  => 'male',
            'U'  => 'child',
            'V'  => 'elementary',
            'W'  => 'Balintawak Elementary School',
            // Member 2
            'X'  => 'Ana',
            'Y'  => '',
            'Z'  => 'dela Cruz',
            'AA' => '2020-07-22',
            'AB' => 'female',
            'AC' => 'child',
            'AD' => 'not_applicable',
            'AE' => '',
        ];

        foreach ($sample as $col => $value) {
            $sheet->setCellValue("{$col}2", $value);
        }

        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
        ]);

        // ── Notes sheet ───────────────────────────────────────────────────────
        $notes = $spreadsheet->createSheet();
        $notes->setTitle('Instructions & Valid Values');

        $noteRows = [
            ['FIELD', 'REQUIRED', 'VALID VALUES / FORMAT', 'NOTES'],
            ['listahanan_id', 'No', 'Any string', 'Must be unique. Leave blank if not available.'],
            ['first_name', 'YES', 'Text', ''],
            ['middle_name', 'No', 'Text', 'Leave blank if none.'],
            ['last_name', 'YES', 'Text', ''],
            ['suffix', 'No', 'Jr., Sr., II, III', 'Leave blank if none.'],
            ['birthdate', 'YES', 'YYYY-MM-DD (e.g. 1990-06-15)', 'Use date format. Must be in the past.'],
            ['sex', 'YES', 'male | female', 'Lowercase.'],
            ['civil_status', 'No', 'single | married | widowed | separated | live-in', 'Defaults to married if blank.'],
            ['contact_number', 'No', 'e.g. 09171234567', ''],
            ['house_no', 'No', 'Text', ''],
            ['street', 'No', 'Text', ''],
            ['purok', 'No', 'e.g. 1-A', ''],
            ['barangay', 'YES', 'Valid Lipa City barangay name', 'Must match an existing barangay in the system.'],
            ['enrollment_date', 'No', 'YYYY-MM-DD', 'Date of 4Ps enrollment.'],
            ['remarks', 'No', 'Text', 'Any additional notes.'],
            ['', '', '', ''],
            ['FAMILY MEMBER COLUMNS', '', '', ''],
            ['member_N_first_name', 'If adding member', 'Text', 'N = 1 to 5. All member fields for N are optional as a group, but first_name, last_name, and birthdate are required if adding any member.'],
            ['member_N_last_name', 'If adding member', 'Text', ''],
            ['member_N_birthdate', 'If adding member', 'YYYY-MM-DD', ''],
            ['member_N_sex', 'No', 'male | female', 'Defaults to female.'],
            ['member_N_relationship', 'No', 'child | spouse | parent | sibling | grandchild | other', 'Defaults to child.'],
            ['member_N_education_level', 'No', 'daycare | preschool | elementary | junior_high | senior_high | not_applicable', 'Auto-computed from age if left blank.'],
            ['member_N_school_name', 'No', 'Text', 'Name of school enrolled in.'],
            ['', '', '', ''],
            ['NOTES', '', '', ''],
            ['- Row 2 (blue italic) is a sample row. Delete or replace it.', '', '', ''],
            ['- All imported beneficiaries start as INACTIVE. Activate them individually after verifying documents.', '', '', ''],
            ['- Up to 5 family members per beneficiary can be added via columns P–BC.', '', '', ''],
            ['- Rows with missing required fields will be skipped and reported in the import summary.', '', '', ''],
            ['- Duplicate Listahanan IDs will be skipped.', '', '', ''],
        ];

        foreach ($noteRows as $ri => $noteRow) {
            $notes->fromArray($noteRow, null, "A" . ($ri + 1));
        }

        $notes->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
        ]);

        foreach (['A', 'B', 'C', 'D'] as $c) {
            $notes->getColumnDimension($c)->setAutoSize(true);
        }

        // ── Write and stream ──────────────────────────────────────────────────
        $writer   = new Xlsx($spreadsheet);
        $filename = 'SECURE-4Ps-Beneficiary-Import-Template.xlsx';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }
}
