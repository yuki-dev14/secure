<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$val = App\Models\SystemSetting::get('maintenance_mode');
echo "Raw Setting Value: " . var_export($val, true) . "\n";
echo "Evaluated Bool: " . var_export((bool)$val, true) . "\n";
