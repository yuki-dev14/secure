<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CLEANING UP CARD ISSUED NOTIFICATIONS FROM DATABASE ===\n";

$deleted = DB::table('notifications')
    ->where('type', 'like', '%CardIssuedNotification%')
    ->orWhere('data', 'like', '%card_issued%')
    ->delete();

echo "Deleted {$deleted} card_issued notifications from database.\n";
