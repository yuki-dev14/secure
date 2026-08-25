<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;

$deleted = Beneficiary::where('listahanan_id', 'like', 'UAT-2026-0002%')->forceDelete();
echo "Cleaned up {$deleted} test records. Total beneficiaries remaining: " . Beneficiary::count() . "\n";
