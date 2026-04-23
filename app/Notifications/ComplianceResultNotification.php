<?php

namespace App\Notifications;

use App\Models\ComplianceRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplianceResultNotification extends Notification
{
    use Queueable;

    public function __construct(public ComplianceRecord $record) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $beneficiary = $notifiable->beneficiary;
        $name        = $beneficiary?->full_name ?? $notifiable->name ?? 'Beneficiary';
        $compliant   = $this->record->is_fully_compliant;
        $period      = $this->record->period;

        return (new MailMessage)
            ->subject(($compliant ? '✅' : '⚠️') . " Compliance Verification Result — {$period} | SECURE 4Ps")
            ->view('emails.distribution-schedule', [
                'subject'      => "Compliance Result — {$period}",
                'greeting'     => "Hello, {$name}!",
                'introLine'    => $compliant
                    ? "Great news! Your household has been verified as COMPLIANT for the period {$period}. You are eligible to receive the cash grant for this period."
                    : "Your household has been marked as NON-COMPLIANT for the period {$period}. Please review the conditions below and coordinate with your assigned verifier.",
                'alertType'    => $compliant ? 'success' : 'warning',
                'detailsTitle' => ($compliant ? '✅' : '⚠️') . " Compliance Summary — {$period}",
                'details'      => array_filter([
                    'Period'              => $period,
                    'Overall Status'      => $compliant ? '✅ COMPLIANT' : '❌ NON-COMPLIANT',
                    'Education'           => $this->record->edu_attendance_compliant ? '✅ Met' : '❌ Not Met',
                    'Health'              => $this->record->health_compliant ? '✅ Met' : '❌ Not Met',
                    'Family Dev. Session' => $this->record->fds_compliant ? '✅ Attended' : '❌ Not Attended',
                    'Verified By'         => $this->record->verifier?->name ?? 'DSWD Verifier',
                    'Override Applied'    => $this->record->is_override ? "Yes — {$this->record->override_reason}" : null,
                ]),
                'noteLines'    => $compliant
                    ? [
                        'Your cash grant for this period will be released during the next scheduled distribution event.',
                        'Continue meeting all co-responsibilities to remain eligible in future periods.',
                    ]
                    : [
                        '⚠ Non-compliance may affect your eligibility for the next cash grant release.',
                        'If you believe this result is incorrect, please visit your BSWC within 5 working days to file an appeal.',
                        'Special circumstances (illness, calamity) may be considered — coordinate with your assigned verifier.',
                    ],
                'actionUrl'    => config('app.url') . '/portal/dashboard',
                'actionText'   => 'View My Compliance',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $compliant = $this->record->is_fully_compliant;
        return [
            'type'    => 'compliance_result',
            'title'   => $compliant ? '✅ Compliance Verified' : '⚠ Non-Compliant Result',
            'message' => $compliant
                ? "You are COMPLIANT for {$this->record->period}. You qualify for the upcoming cash grant."
                : "You are NON-COMPLIANT for {$this->record->period}. Please visit your BSWC.",
            'details' => [
                'period'       => $this->record->period,
                'is_compliant' => $compliant,
            ],
        ];
    }
}
