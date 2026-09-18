<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\Beneficiary;

echo "=== GENERATING 5 NEW MATAAS NA LUPA BENEFICIARIES (SET 2) ===\n";

$headers = [
    'listahanan_id',
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'birthdate',
    'sex',
    'civil_status',
    'contact_number',
    'house_no',
    'street',
    'purok',
    'barangay',
    'enrollment_date',
    'remarks',
    // Member 1
    'member_1_first_name',
    'member_1_middle_name',
    'member_1_last_name',
    'member_1_birthdate',
    'member_1_sex',
    'member_1_relationship',
    'member_1_education_level',
    'member_1_school_name',
    // Member 2
    'member_2_first_name',
    'member_2_middle_name',
    'member_2_last_name',
    'member_2_birthdate',
    'member_2_sex',
    'member_2_relationship',
    'member_2_education_level',
    'member_2_school_name',
    // Member 3
    'member_3_first_name',
    'member_3_middle_name',
    'member_3_last_name',
    'member_3_birthdate',
    'member_3_sex',
    'member_3_relationship',
    'member_3_education_level',
    'member_3_school_name',
];

$beneficiaries = [
    [
        'SIM-MNL-2026-006', 'Rowena', 'Santos', 'Dalisay', '', '1988-04-12', 'female', 'married', '09223345106', '77', 'G. Dimayuga St.', 'Purok 1', 'Mataas na Lupa', '2022-08-15', 'Mataas na Lupa Simulation 6',
        'Marco', 'Santos', 'Dalisay', '2008-09-20', 'male', 'child', 'senior_high', 'Lipa City National High School',
        'Samantha', 'Santos', 'Dalisay', '2013-03-11', 'female', 'child', 'elementary', 'Mataas na Lupa Elementary School',
        'Beatrice', 'Santos', 'Dalisay', '2018-07-29', 'female', 'child', 'daycare', 'Mataas na Lupa Daycare Center'
    ],
    [
        'SIM-MNL-2026-007', 'Arnel', 'Mendoza', 'Dimaculangan', '', '1984-11-23', 'male', 'married', '09233345107', '104', 'P. Burgos St.', 'Purok 2', 'Mataas na Lupa', '2021-05-20', 'Mataas na Lupa Simulation 7',
        'Justin', 'Mendoza', 'Dimaculangan', '2010-06-15', 'male', 'child', 'junior_high', 'Lipa City Science High School',
        'Princess', 'Mendoza', 'Dimaculangan', '2015-10-08', 'female', 'child', 'elementary', 'Mataas na Lupa Elementary School',
        '', '', '', '', '', '', '', ''
    ],
    [
        'SIM-MNL-2026-008', 'Jocelyn', 'Reyes', 'Macasaet', '', '1986-07-30', 'female', 'widowed', '09243345108', '32', 'San Carlos St.', 'Purok 3', 'Mataas na Lupa', '2020-10-12', 'Mataas na Lupa Simulation 8',
        'Andrei', 'Reyes', 'Macasaet', '2009-02-18', 'male', 'child', 'senior_high', 'Inosloban-Marawoy National High School',
        'Camille', 'Reyes', 'Macasaet', '2014-11-25', 'female', 'child', 'elementary', 'Mataas na Lupa Elementary School',
        '', '', '', '', '', '', '', ''
    ],
    [
        'SIM-MNL-2026-009', 'Roderick', 'Caringal', 'Ilagan', '', '1992-02-14', 'male', 'married', '09253345109', '51', 'Banay-Banay Rd', 'Purok 4', 'Mataas na Lupa', '2023-04-10', 'Mataas na Lupa Simulation 9',
        'Nathan', 'Caringal', 'Ilagan', '2012-08-04', 'male', 'child', 'junior_high', 'Mataas na Lupa National High School',
        'Ethan', 'Caringal', 'Ilagan', '2017-01-19', 'male', 'child', 'elementary', 'Mataas na Lupa Elementary School',
        'Chloe', 'Caringal', 'Ilagan', '2022-03-30', 'female', 'child', 'daycare', 'Mataas na Lupa Child Development Center'
    ],
    [
        'SIM-MNL-2026-010', 'Glenda', 'Perez', 'Tolentino', '', '1993-09-08', 'female', 'single', '09263345110', '89', 'Katipunan St.', 'Purok 5', 'Mataas na Lupa', '2022-01-18', 'Mataas na Lupa Simulation 10',
        'Angelo', 'Perez', 'Tolentino', '2011-12-05', 'male', 'child', 'junior_high', 'Mataas na Lupa National High School',
        '', '', '', '', '', '', '', '',
        '', '', '', '', '', '', '', ''
    ],
];

// Check for collision in DB
$ids = array_column($beneficiaries, 0);
$existing = Beneficiary::whereIn('listahanan_id', $ids)->pluck('listahanan_id')->toArray();
if (!empty($existing)) {
    echo "WARNING: IDs already exist in database: " . implode(', ', $existing) . "\n";
    exit(1);
} else {
    echo "✓ ID uniqueness check passed: No collisions in database for " . implode(', ', $ids) . "\n";
}

// Ensure datasets directory exists
$targetDir = __DIR__ . '/../datasets';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// 1. Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Beneficiaries');

// Headers
foreach ($headers as $colIdx => $header) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
    $sheet->setCellValue("{$colLetter}1", $header);
}

// Data
foreach ($beneficiaries as $rowIdx => $row) {
    $rowNumber = $rowIdx + 2;
    foreach ($row as $colIdx => $val) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
        $sheet->setCellValue("{$colLetter}{$rowNumber}", $val);
    }
}

// Styling
$lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
$sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
]);

// Auto-size columns
for ($i = 1; $i <= count($headers); $i++) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}
$sheet->getRowDimension(1)->setRowHeight(26);

// Save Excel file
$xlsxPath = $targetDir . '/MATAAS_NA_LUPA_SET2_5_BENEFICIARIES.xlsx';
$writerXlsx = new Xlsx($spreadsheet);
$writerXlsx->save($xlsxPath);
echo "✓ Saved XLSX: " . realpath($xlsxPath) . "\n";

// Save CSV file
$csvPath = $targetDir . '/MATAAS_NA_LUPA_SET2_5_BENEFICIARIES.csv';
$writerCsv = new Csv($spreadsheet);
$writerCsv->save($csvPath);
echo "✓ Saved CSV:  " . realpath($csvPath) . "\n";

echo "\nSummary of 5 New Mataas na Lupa Beneficiaries:\n";
foreach ($beneficiaries as $b) {
    echo "- [{$b[0]}] {$b[1]} {$b[3]} -> Barangay: {$b[12]} (Phone: {$b[8]})\n";
}
