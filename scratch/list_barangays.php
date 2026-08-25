<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$barangays = \App\Models\Beneficiary::distinct()->orderBy('barangay')->pluck('barangay');
echo "BARANGAYS IN BENEFICIARIES:\n";
foreach ($barangays as $b) {
    $count = \App\Models\Beneficiary::where('barangay', $b)->count();
    echo "- {$b} ({$count} beneficiaries)\n";
}
