<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$assistants = App\Models\User::where('role', 'barangay_assistant')->orderBy('name')->get();
foreach ($assistants as $a) {
    echo "Name: {$a->name} | Username: {$a->username} | Email: {$a->email} | Brgy: {$a->assigned_barangay}\n";
}
