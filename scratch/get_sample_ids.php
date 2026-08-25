<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$beneficiaries = App\Models\Beneficiary::all();
foreach ($beneficiaries as $b) {
    echo "ID: {$b->unique_id} | Name: {$b->full_name} | Barangay: {$b->barangay}\n";
}
