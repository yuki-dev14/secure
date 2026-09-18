<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\BeneficiaryImport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

echo "=== DRY-RUN TEST OF MATAAS_NA_LUPA_5_BENEFICIARIES.xlsx ===\n";

$superadmin = User::where('role', 'superadmin')->first();
$filePath = __DIR__ . '/../datasets/MATAAS_NA_LUPA_5_BENEFICIARIES.xlsx';

DB::beginTransaction();
try {
    $import = new BeneficiaryImport($superadmin->id);
    Excel::import($import, $filePath);

    echo "Import Result:\n";
    echo " - Success Count: {$import->successCount} / 5\n";
    echo " - Skip Count:    {$import->skipCount}\n";

    if (!empty($import->skipped)) {
        echo "Skipped details:\n";
        print_r($import->skipped);
    } else {
        echo "✓ PERFECT! All 5 Mataas na Lupa beneficiaries validated and imported with 0 errors/skips!\n";
    }

    echo " - Generated Unique IDs: " . implode(', ', $import->importedIds) . "\n";
} catch (\Throwable $e) {
    echo "ERROR during import: " . $e->getMessage() . "\n";
} finally {
    // ALWAYS ROLLBACK to keep DB clean for their actual test
    DB::rollBack();
    echo "\n[INFO] Transaction rolled back. DB remains fresh for your live test!\n";
}
