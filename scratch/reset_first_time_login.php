<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;
use App\Models\BeneficiaryCard;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$beneficiaries = Beneficiary::with(['user', 'cards'])->get();
$count = 0;

foreach ($beneficiaries as $b) {
    // 1. Reset linked User account
    if ($b->user) {
        $b->user->update([
            'password'             => Hash::make('Beneficiary@1234!'),
            'must_change_password' => true,
        ]);
    }

    // 2. Reset active BeneficiaryCard
    $activeCard = $b->cards()->where('is_active', true)->first();
    if ($activeCard) {
        $activeCard->update([
            'is_first_login'         => true,
            'password_changed_at'    => null,
            'default_password_plain' => 'Beneficiary@1234!',
            'default_password_hash'  => Hash::make('Beneficiary@1234!'),
        ]);
    }

    $count++;
}

echo "Successfully reset {$count} beneficiary accounts!\n";
echo "Default Password for ALL beneficiaries: Beneficiary@1234!\n";
echo "First-Time Password Change screen (must_change_password) is now ENABLED for testing.\n";
