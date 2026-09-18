<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;

$beneficiary = Beneficiary::where('status', 'active')->first();
$superadmin = User::where('role', 'superadmin')->first();
$beneficiaryUser = $beneficiary->user ?? User::where('role', 'beneficiary')->first();

// 1. Notification Trigger (Mark read notification)
Auth::login($beneficiaryUser);
$notif = DB::table('notifications')->where('notifiable_id', $beneficiaryUser->id)->first();
$notifId = $notif ? $notif->id : 'a0000000-0000-0000-0000-000000000000';

$notifTimes = [];
for ($i = 0; $i < 5; $i++) {
    $req = Request::create("/portal/notifications/{$notifId}/read", 'PATCH');
    $req->setUserResolver(fn() => $beneficiaryUser);
    $t0 = microtime(true);
    $res = $app->make(Kernel::class)->handle($req);
    $notifTimes[] = round(microtime(true) - $t0, 3);
}
echo "Notification Trigger: " . implode(', ', $notifTimes) . " (Avg: " . round(array_sum($notifTimes)/5, 3) . "s)\n";

// 2. QR Attendance Scan (Check-In and Check-Out)
Auth::login($superadmin);
$qrTimes = [];
for ($i = 0; $i < 5; $i++) {
    $req = Request::create('/admin4ps/fds-attendance/scan', 'POST', [
        'payload'       => $beneficiary->unique_id,
        'scan_type'     => ($i % 2 === 0 ? 'check_in' : 'check_out'),
        'period'        => '2026-P5',
        'session_title' => 'FDS Performance Test Session',
        'venue'         => 'Brgy. Cultural Center'
    ]);
    $req->setUserResolver(fn() => $superadmin);
    $t0 = microtime(true);
    $res = $app->make(Kernel::class)->handle($req);
    $qrTimes[] = round(microtime(true) - $t0, 3);
}
echo "QR Attendance Scan: " . implode(', ', $qrTimes) . " (Avg: " . round(array_sum($qrTimes)/5, 3) . "s)\n";
