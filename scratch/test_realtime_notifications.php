<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;
use App\Models\FdsAttendance;
use App\Notifications\FdsAttendanceNotification;
use App\Notifications\GrantUpdatedNotification;

echo "=== CREATING SAMPLE FDS & GRANT NOTIFICATIONS FOR TEST BENEFICIARIES ===\n";

$beneficiaries = Beneficiary::with('user')->whereNotNull('user_id')->limit(10)->get();

foreach ($beneficiaries as $b) {
    if (!$b->user) continue;

    // Create a mock FdsAttendance instance for notification demo
    $att = new FdsAttendance([
        'session_title'  => 'Monthly FDS Session — Health & Family Welfare',
        'venue'          => "Brgy. {$b->barangay} Cultural Center",
        'session_date'   => now()->subDays(2),
        'checked_in_at'  => now()->subDays(2)->setHour(8)->setMinute(30),
        'checked_out_at' => now()->subDays(2)->setHour(11)->setMinute(45),
        'is_complete'    => true,
    ]);

    // Send check-in notification
    $b->user->notify(new FdsAttendanceNotification($att, 'check_in'));
    // Send check-out notification
    $b->user->notify(new FdsAttendanceNotification($att, 'check_out'));

    // Send grant updated notification
    $b->user->notify(new GrantUpdatedNotification(
        '2026-P4',
        2700.00,
        1500.00,
        0.00,
        1200.00,
        'Fully eligible for P4 grant distribution'
    ));

    echo "Notified {$b->full_name} ({$b->unique_id})\n";
}

echo "✅ DONE!\n";
