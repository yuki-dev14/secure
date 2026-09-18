<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Beneficiary;

echo "=== BARANGAY ASSISTANTS ===\n";
$assistants = User::where('role', 'barangay_assistant')->get(['id', 'name', 'username', 'role', 'assigned_barangay', 'is_active']);
foreach ($assistants as $a) {
    echo "ID: {$a->id} | Name: {$a->name} | Username: {$a->username} | Assigned: '{$a->assigned_barangay}' | Active: " . ($a->is_active ? 'YES' : 'NO') . "\n";
}

echo "\n=== ALL USERS WITH ROLES ===\n";
$roles = User::selectRaw('role, count(*) as count')->groupBy('role')->get();
foreach ($roles as $r) {
    echo "Role: {$r->role} -> Count: {$r->count}\n";
}

echo "\n=== BENEFICIARY COUNT & MAX LISTAHANAN ID ===\n";
echo "Total Beneficiaries: " . Beneficiary::count() . "\n";
$listIds = Beneficiary::whereNotNull('listahanan_id')->pluck('listahanan_id');
echo "Sample Listahanan IDs:\n";
foreach ($listIds->take(15) as $id) {
    echo " - $id\n";
}
