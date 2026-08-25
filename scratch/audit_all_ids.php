<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;
use App\Models\User;

echo "=== AUDIT OF EXISTING BENEFICIARY DATABASE RECORDS ===\n";
$existingListahananIds = Beneficiary::pluck('listahanan_id')->filter()->toArray();
$existingUsernames = User::pluck('username')->toArray();
$existingUniqueIds = Beneficiary::pluck('unique_id')->toArray();

echo "Existing Listahanan IDs in DB (" . count($existingListahananIds) . "):\n";
print_r($existingListahananIds);

echo "\nHighest Unique ID in DB: " . (Beneficiary::orderByRaw('CAST(SUBSTRING(unique_id FROM 9) AS INTEGER) DESC')->first()?->unique_id ?? 'None') . "\n";

echo "\n=== AUDIT OF UAT SAMPLE DATA CSV (public/uat_beneficiaries_sample.csv) ===\n";
$csvFile = public_path('uat_beneficiaries_sample.csv');
$lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$header = str_getcsv(array_shift($lines));
$csvListahananIds = [];

foreach ($lines as $line) {
    $row = str_getcsv($line);
    if (empty(array_filter($row))) continue;
    $csvListahananIds[] = $row[0]; // First column is listahanan_id
}

echo "CSV Listahanan IDs (" . count($csvListahananIds) . "):\n";
print_r($csvListahananIds);

echo "\n=== CHECKING FOR ANY LISTAHANAN ID OVERLAP ===\n";
$overlap = array_intersect($existingListahananIds, $csvListahananIds);
if (empty($overlap)) {
    echo "✅ CONFIRMED: ZERO Listahanan ID overlap between DB and CSV!\n";
} else {
    echo "❌ OVERLAP FOUND: " . implode(', ', $overlap) . "\n";
}

echo "\n=== CHECKING FUTURE USERNAME GENERATION OVERLAP ===\n";
$nextSeqStart = 11; // Since highest existing is 4PS-LPA-000010
$conflictingUsernames = [];
for ($i = 0; $i < count($csvListahananIds); $i++) {
    $seq = $nextSeqStart + $i;
    $futureUniqueId = '4PS-LPA-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    $futureUsername = strtolower(str_replace('-', '', $futureUniqueId)); // e.g. 4pslpa000011
    if (in_array($futureUsername, $existingUsernames)) {
        $conflictingUsernames[] = $futureUsername;
    }
}

if (empty($conflictingUsernames)) {
    echo "✅ CONFIRMED: ZERO Username collisions for future portal users!\n";
} else {
    echo "❌ USERNAME OVERLAP FOUND: " . implode(', ', $conflictingUsernames) . "\n";
}
