<?php

namespace App\Notifications;

use App\Models\FdsAttendance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FdsAttendanceNotification extends Notification
{
    use Queueable;

    public function __construct(
        public FdsAttendance $attendance,
        public string $type = 'check_in'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $venue   = $this->attendance->venue ?? ("Brgy. " . ($notifiable->beneficiary?->barangay ?? 'Center'));
        $dateStr = $this->attendance->session_date ? $this->attendance->session_date->format('M d, Y') : now()->format('M d, Y');

        if ($this->type === 'check_out') {
            $timeStr = $this->attendance->checked_out_at ? $this->attendance->checked_out_at->format('g:i A') : now()->format('g:i A');
            return [
                'type'    => 'fds_check_out',
                'title'   => '✅ FDS Session Completed (Checked Out)',
                'message' => "Your FDS attendance check-out was recorded at {$venue} on {$dateStr} at {$timeStr}. Session completed successfully!",
                'details' => [
                    'session_title'  => $this->attendance->session_title,
                    'venue'          => $venue,
                    'session_date'   => $dateStr,
                    'checked_out_at' => $timeStr,
                    'is_complete'    => true,
                ],
            ];
        }

        $timeStr = $this->attendance->checked_in_at ? $this->attendance->checked_in_at->format('g:i A') : now()->format('g:i A');
        return [
            'type'    => 'fds_check_in',
            'title'   => '🎯 FDS Attendance Check-In Recorded',
            'message' => "Your FDS attendance check-in was scanned and recorded at {$venue} on {$dateStr} at {$timeStr}.",
            'details' => [
                'session_title' => $this->attendance->session_title,
                'venue'         => $venue,
                'session_date'  => $dateStr,
                'checked_in_at' => $timeStr,
            ],
        ];
    }
}
