<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Beneficiaries');

$sheet->setCellValue('M1', 'barangay');

$barangayValidation = $sheet->getCell('M2')->getDataValidation();
$barangayValidation->setType(DataValidation::TYPE_LIST);
$barangayValidation->setErrorStyle(DataValidation::STYLE_STOP);
$barangayValidation->setAllowBlank(false);
$barangayValidation->setShowInputMessage(true);
$barangayValidation->setShowErrorMessage(true);
$barangayValidation->setShowDropDown(true);
$barangayValidation->setErrorTitle('Invalid Barangay');
$barangayValidation->setError('Please select a valid Lipa City barangay from the dropdown.');
$barangayValidation->setPromptTitle('Select Barangay');
$barangayValidation->setPrompt('Choose from the list of valid Lipa City barangays.');
$barangayValidation->setFormula1('"Anilao,Antipolo del Norte,Bagong Pook,Balintawak,Banaybanay,Bolbok,Dagatan,Inosloban,Kayumanggi,Lipa City Poblacion,Lodlod,Marawoy,Mataas na Lupa,Pinagkawitan,Sabang,Sico,Tambo,Tibig"');

for ($row = 2; $row <= 100; $row++) {
    $sheet->getCell("M{$row}")->setDataValidation(clone $barangayValidation);
}

$writer = new Xlsx($spreadsheet);
$tempFile = sys_get_temp_dir() . '/test_template.xlsx';
$writer->save($tempFile);

echo "SUCCESS: Saved template with barangay dropdown to {$tempFile}\n";
