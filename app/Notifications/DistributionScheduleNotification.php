<?php

namespace App\Notifications;

use App\Models\DistributionEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DistributionScheduleNotification extends Notification
{
    use Queueable;

    public function __construct(public DistributionEvent $event) {}

    /** Deliver via both in-app database AND email */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Only add mail channel if the user has an email address
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $beneficiary = $notifiable->beneficiary;
        $name        = $beneficiary?->full_name ?? $notifiable->name ?? 'Beneficiary';

        return (new MailMessage)
            ->subject("✅ Quarter {$this->event->period} Is Now Open | SECURE 4Ps")
            ->view('emails.distribution-schedule', [
                'subject'      => "Quarter {$this->event->period} Is Now Open",
                'greeting'     => "Hello, {$name}!",
                'introLine'    => "The {$this->event->period} quarter is now open! "
                    . "Submit your requirements to the local DSWD office to be verified by the Compliance Verifier. "
                    . "Once you are verified as compliant this quarter, your cash grant will be available for claiming — anytime before the quarter ends.",
                'alertType'    => 'success',
                'detailsTitle' => '✅ What To Do This Quarter',
                'details'      => [
                    'Quarter'        => $this->event->period,
                    'Quarter Ends'   => optional($this->event->period_end)->format('F d, Y') ?? '—',
                    'Venue'          => $this->event->venue ?? 'DSWD Office',
                    'Office Hours'   => $this->event->distribution_time_start
                        ? "{$this->event->distribution_time_start} – " . ($this->event->distribution_time_end ?? '—')
                        : 'During regular office hours',
                ],
                'noteLines' => [
                    '📝 Step 1: Complete all required activities — health, education attendance, and FDS.',
                    '🏢 Step 2: Submit your requirements to the local DSWD office to be verified by the Compliance Verifier.',
                    '💰 Step 3: Once verified as compliant, your cash grant will be computed and you may claim it at the office.',
                    '⚠ Important: Bring your SECURE 4Ps ID card and one (1) valid government-issued ID when claiming.',
                    'If you cannot claim personally, your registered authorized proxy may claim on your behalf.',
                    "Claims must be completed before " . (optional($this->event->period_end)->format('F d, Y') ?? 'the quarter ends') . ".",
                ],
                'actionUrl'    => config('app.url') . '/portal/dashboard',
                'actionText'   => 'View My Portal',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'distribution_ongoing',
            'title'   => "✅ Quarter {$this->event->period} Is Now Open",
            'message' => "The {$this->event->period} quarter is now open! Submit your requirements to the DSWD office to be verified. "
                . "Once verified as compliant, your cash grant will be available for claiming before "
                . (optional($this->event->period_end)->format('F d, Y') ?? 'the quarter ends') . ".",
            'details' => [
                'event_id'      => $this->event->id,
                'period'        => $this->event->period,
                'venue'         => $this->event->venue,
                'quarter_start' => optional($this->event->period_start)->format('F d, Y'),
                'quarter_end'   => optional($this->event->period_end)->format('F d, Y'),
                'time_start'    => $this->event->distribution_time_start,
            ],
        ];
    }
}
