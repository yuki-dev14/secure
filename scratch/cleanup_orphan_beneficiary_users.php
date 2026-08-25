<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;
use App\Models\User;

$activeBeneficiaryUserIds = Beneficiary::pluck('user_id')->filter()->toArray();
$orphans = User::where('role', 'beneficiary')
    ->whereNotIn('id', $activeBeneficiaryUserIds)
    ->get();

echo "Found " . $orphans->count() . " orphan beneficiary user accounts.\n";
foreach ($orphans as $orphan) {
    echo " - Deleting orphan user: ID {$orphan->id} | Username: {$orphan->username} | Name: {$orphan->name}\n";
    $orphan->forceDelete();
}

echo "Cleanup finished. Current active beneficiary users count: " . User::where('role', 'beneficiary')->count() . "\n";
