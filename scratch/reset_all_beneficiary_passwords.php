<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$passwordHash = Hash::make('Beneficiary@1234!');

// 1. Update all beneficiary users
$updatedUsers = DB::table('users')
    ->where('role', 'beneficiary')
    ->update([
        'password'             => $passwordHash,
        'must_change_password' => true,
    ]);

// 2. Update active beneficiary cards
$updatedCards = DB::table('beneficiary_cards')
    ->where('is_active', true)
    ->update([
        'is_first_login'         => true,
        'password_changed_at'    => null,
        'default_password_plain' => 'Beneficiary@1234!',
        'default_password_hash'  => $passwordHash,
    ]);

echo "Updated {$updatedUsers} beneficiary users and {$updatedCards} active cards!\n";
echo "Default Password for ALL beneficiaries: Beneficiary@1234!\n";
echo "First-Time Password Change screen (must_change_password) is now ACTIVE for testing.\n";
