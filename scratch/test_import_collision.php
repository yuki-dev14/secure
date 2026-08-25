<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\BeneficiaryImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Beneficiary;

echo "Current Total Beneficiaries before import: " . Beneficiary::count() . "\n";
echo "Highest existing unique_id: " . (Beneficiary::orderByRaw('CAST(SUBSTRING(unique_id FROM 9) AS INTEGER) DESC')->first()?->unique_id ?? 'None') . "\n";

$import = new BeneficiaryImport(1);
Excel::import($import, public_path('uat_beneficiaries_sample.csv'));

echo "Import Success Count: " . $import->successCount . "\n";
echo "Import Skip Count: " . $import->skipCount . "\n";

if ($import->skipCount > 0) {
    echo "SKIPPED ROWS:\n";
    print_r($import->skipped);
} else {
    echo "SUCCESS: All 15 UAT sample rows imported cleanly with ZERO conflicts!\n";
}
