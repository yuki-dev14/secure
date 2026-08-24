<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CASH GRANT CALCULATIONS ===\n";
foreach (\App\Models\CashGrantCalculation::with(['beneficiary', 'distributionEvent'])->get() as $g) {
    echo "ID: {$g->id} | Beneficiary: {$g->beneficiary?->full_name} | Event Period: {$g->distributionEvent?->period} | Total: ₱{$g->total_grant_amount} | Created: {$g->created_at}\n";
}
