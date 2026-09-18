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

echo "=== GENERATING 5 MATAAS NA LUPA BENEFICIARIES EXCEL & CSV ===\n";

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
        'SIM-MNL-2026-001', 'Teresa', 'Bautista', 'Castillo', '', '1989-12-19', 'female', 'married', '09173345101', '120', 'A. Mabini St.', 'Purok 1', 'Mataas na Lupa', '2022-06-18', 'Mataas na Lupa Simulation 1',
        'Christian', 'Bautista', 'Castillo', '2008-01-09', 'male', 'child', 'senior_high', 'Lipa City National High School',
        'Angelica', 'Bautista', 'Castillo', '2012-05-14', 'female', 'child', 'junior_high', 'Lipa City Science High School',
        'Mia', 'Bautista', 'Castillo', '2016-08-21', 'female', 'child', 'elementary', 'Mataas na Lupa Elementary School'
    ],
    [
        'SIM-MNL-2026-002', 'Eduardo', 'Ramos', 'Villanueva', 'Jr.', '1985-08-14', 'male', 'married', '09183345102', '45', 'Mataas na Lupa Main Rd', 'Purok 2', 'Mataas na Lupa', '2021-03-15', 'Mataas na Lupa Simulation 2',
        'Joshua', 'Ramos', 'Villanueva', '2014-04-18', 'male', 'child', 'elementary', 'Mataas na Lupa Elementary School',
        'Ella', 'Ramos', 'Villanueva', '2021-09-05', 'female', 'child', 'daycare', 'Mataas na Lupa Daycare Center',
        '', '', '', '', '', '', '', ''
    ],
    [
        'SIM-MNL-2026-003', 'Marites', 'de Castro', 'Mercado', '', '1991-03-22', 'female', 'married', '09193345103', '88', 'San Carlos St.', 'Purok 3', 'Mataas na Lupa', '2022-11-10', 'Mataas na Lupa Simulation 3',
        'Daniel', 'de Castro', 'Mercado', '2010-10-15', 'male', 'child', 'junior_high', 'Inosloban-Marawoy National High School',
        'Chloe', 'de Castro', 'Mercado', '2015-02-28', 'female', 'child', 'elementary', 'Mataas na Lupa Elementary School',
        'Lucas', 'de Castro', 'Mercado', '2021-06-12', 'male', 'child', 'daycare', 'Mataas na Lupa Child Development Center'
    ],
    [
        'SIM-MNL-2026-004', 'Lorna', 'Alvarez', 'Hernandez', '', '1987-10-05', 'female', 'widowed', '09203345104', '15', 'Old Barangay Rd', 'Purok 4', 'Mataas na Lupa', '2020-09-01', 'Mataas na Lupa Simulation 4',
        'Gabriel', 'Alvarez', 'Hernandez', '2007-11-30', 'male', 'child', 'senior_high', 'Lipa City National High School',
        '', '', '', '', '', '', '', '',
        '', '', '', '', '', '', '', ''
    ],
    [
        'SIM-MNL-2026-005', 'Renato', 'Gutierrez', 'Navarro', '', '1990-05-18', 'male', 'married', '09213345105', '62', 'Katipunan Ext.', 'Purok 5', 'Mataas na Lupa', '2023-01-25', 'Mataas na Lupa Simulation 5',
        'John Paul', 'Gutierrez', 'Navarro', '2011-07-08', 'male', 'child', 'junior_high', 'Mataas na Lupa National High School',
        'Patricia', 'Gutierrez', 'Navarro', '2016-12-03', 'female', 'child', 'elementary', 'Mataas na Lupa Elementary School',
        '', '', '', '', '', '', '', ''
    ],
];

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
$xlsxPath = __DIR__ . '/../MATAAS_NA_LUPA_5_BENEFICIARIES.xlsx';
$writerXlsx = new Xlsx($spreadsheet);
$writerXlsx->save($xlsxPath);
echo "✓ Saved XLSX: " . realpath($xlsxPath) . "\n";

// Save CSV file
$csvPath = __DIR__ . '/../MATAAS_NA_LUPA_5_BENEFICIARIES.csv';
$writerCsv = new Csv($spreadsheet);
$writerCsv->save($csvPath);
echo "✓ Saved CSV:  " . realpath($csvPath) . "\n";

echo "\nSummary of 5 Mataas na Lupa Beneficiaries:\n";
foreach ($beneficiaries as $b) {
    echo "- [{$b[0]}] {$b[1]} {$b[3]} -> Barangay: {$b[12]}\n";
}
