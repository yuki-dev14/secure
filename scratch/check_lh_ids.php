<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;

foreach (Beneficiary::all() as $b) {
    echo "ID: {$b->id} | UniqueID: {$b->unique_id} | ListahananID: '{$b->listahanan_id}' | Name: {$b->first_name} {$b->last_name}\n";
}
