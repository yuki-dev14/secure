<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\BeneficiaryImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Beneficiary;

echo "=== TESTING ADM-006: Invalid Beneficiary Import File ===\n";
$initialCount = Beneficiary::count();
$import006 = new BeneficiaryImport(1);
Excel::import($import006, public_path('downloads/uat/ADM-006_Invalid_Beneficiary_Import.csv'));

echo "Success Count: {$import006->successCount}\n";
echo "Skip Count: {$import006->skipCount}\n";
echo "Skipped Details:\n";
foreach ($import006->skipped as $skip) {
    echo " - Row {$skip['row']}: {$skip['reason']}\n";
}
$newCount006 = Beneficiary::count();
echo "Beneficiary database count before: {$initialCount}, after: {$newCount006}\n";
if ($import006->successCount === 0 && $import006->skipCount === 3 && $initialCount === $newCount006) {
    echo "✅ TEST CASE ADM-006 PASSED 100%!\n\n";
} else {
    echo "❌ TEST CASE ADM-006 FAILED!\n\n";
}

echo "=== TESTING ADM-007: Duplicate Beneficiary Identifier ===\n";
$initialCount007 = Beneficiary::count();
$import007 = new BeneficiaryImport(1);
Excel::import($import007, public_path('downloads/uat/ADM-007_Duplicate_Beneficiary_Import.csv'));

echo "Success Count: {$import007->successCount}\n";
echo "Skip Count: {$import007->skipCount}\n";
echo "Skipped Details:\n";
foreach ($import007->skipped as $skip) {
    echo " - Row {$skip['row']}: {$skip['reason']}\n";
}
$newCount007 = Beneficiary::count();
echo "Beneficiary database count before: {$initialCount007}, after: {$newCount007}\n";
if ($import007->successCount === 0 && $import007->skipCount === 1 && $initialCount007 === $newCount007) {
    echo "✅ TEST CASE ADM-007 PASSED 100%!\n";
} else {
    echo "❌ TEST CASE ADM-007 FAILED!\n";
}
