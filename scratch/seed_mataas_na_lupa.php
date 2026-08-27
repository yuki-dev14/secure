<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Imports\BeneficiaryImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Beneficiary;
use App\Models\User;
use App\Services\BeneficiaryCardService;
use Illuminate\Support\Facades\Hash;

echo "=== SEEDING 10 MATAAS NA LUPA BENEFICIARIES FOR TOMORROW'S UAT ===\n";

$csvFile = public_path('downloads/uat/UAT_Mataas_Na_Lupa_10_Beneficiaries.csv');
$import  = new BeneficiaryImport(1);
Excel::import($import, $csvFile);

echo "Import Success Count: {$import->successCount}\n";
echo "Import Skip Count: {$import->skipCount}\n";

if ($import->skipCount > 0) {
    foreach ($import->skipped as $s) {
        echo " - Skipped Row {$s['row']}: {$s['reason']}\n";
    }
}

// Issue 4Ps QR cards and set clean credentials for all Mataas na Lupa beneficiaries
$cardService = app(BeneficiaryCardService::class);
$mnlBeneficiaries = Beneficiary::where('barangay', 'Mataas na Lupa')
    ->where('listahanan_id', 'like', 'UAT-MNL-%')
    ->get();

echo "\nCONFIGURING 4PS QR CARDS & CREDENTIALS:\n";
foreach ($mnlBeneficiaries as $index => $b) {
    // Ensure active status
    $b->update([
        'status'       => 'active',
        'is_compliant' => true,
    ]);

    // Issue card
    $card = $cardService->issueCard($b, 1);

    // Set portal user credentials
    if ($b->user) {
        $cleanUsername = strtolower(str_replace(' ', '.', $b->first_name)) . '.mnl';
        // Remove accents and special chars
        $cleanUsername = preg_replace('/[^a-z0-9.]/', '', $cleanUsername);

        $b->user->update([
            'username'             => $cleanUsername,
            'email'                => "{$cleanUsername}@4ps.lipa.gov.ph",
            'password'             => Hash::make('Beneficiary@1234!'),
            'is_active'            => true,
            'must_change_password' => false,
        ]);
    }

    echo sprintf(
        "%02d. %-30s | ID: %-14s | User: %-25s | Pass: Beneficiary@1234!\n",
        $index + 1,
        $b->full_name,
        $b->unique_id,
        $b->user?->username ?? 'N/A'
    );
}

echo "\n✅ ALL 10 MATAAS NA LUPA BENEFICIARIES SUCCESSFULLY SEEDED AND ACTIVATED!\n";
