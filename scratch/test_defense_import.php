<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\BeneficiaryImport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

echo "=== DRY-RUN TEST OF FINAL_DEFENSE_20_BENEFICIARIES.xlsx ===\n";

$superadmin = User::where('role', 'superadmin')->first();
$filePath = __DIR__ . '/../datasets/FINAL_DEFENSE_20_BENEFICIARIES.xlsx';

// Test inside a transaction with rollback so we verify without prematurely committing
DB::beginTransaction();
try {
    $import = new BeneficiaryImport($superadmin->id);
    Excel::import($import, $filePath);

    echo "Import Result:\n";
    echo " - Success Count: {$import->successCount} / 20\n";
    echo " - Skip Count:    {$import->skipCount}\n";

    if (!empty($import->skipped)) {
        echo "Skipped details:\n";
        print_r($import->skipped);
    } else {
        echo "✓ PERFECT! All 20 beneficiaries validated and imported with 0 errors/skips!\n";
    }

    echo " - Sample Generated Unique IDs: " . implode(', ', array_slice($import->importedIds, 0, 5)) . "...\n";
} catch (\Throwable $e) {
    echo "ERROR during import: " . $e->getMessage() . "\n" . $e->getTraceAsString();
} finally {
    // ALWAYS ROLLBACK so the database is kept completely fresh for tomorrow's defense!
    DB::rollBack();
    echo "\n[INFO] Transaction rolled back. Database is clean and ready for your live import tomorrow!\n";
}
