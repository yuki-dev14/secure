<?php

namespace App\Notifications;

use App\Models\DistributionEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to all active beneficiaries when a new distribution event is created (status = upcoming).
 * Informs them of the upcoming quarter and where to claim.
 */
class DistributionEventCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public DistributionEvent $event) {}

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

        return (new MailMessage)
            ->subject("📊 Quarter {$this->event->period} Opening Soon | SECURE 4Ps")
            ->view('emails.distribution-schedule', [
                'subject'      => "Quarter {$this->event->period} Is Opening Soon",
                'greeting'     => "Hello, {$name}!",
                'introLine'    => "The {$this->event->period} quarter is going to be open soon. "
                    . "Prepare your requirements now to be eligible for your cash grant. "
                    . "Make sure all health, education, and FDS requirements are ready before the quarter begins.",
                'alertType'    => 'info',
                'detailsTitle' => '📊 Quarter Details',
                'details'      => [
                    'Quarter'        => $this->event->period,
                    'Quarter Opens'  => optional($this->event->period_start)->format('F d, Y') ?? '—',
                    'Quarter Closes' => optional($this->event->period_end)->format('F d, Y') ?? '—',
                    'Venue'          => $this->event->venue ?? 'DSWD Office',
                    'Office Hours'   => $this->event->distribution_time_start
                        ? "{$this->event->distribution_time_start} – " . ($this->event->distribution_time_end ?? '—')
                        : 'During regular office hours',
                ],
                'noteLines' => [
                    '✅ Health: Ensure all family members have completed their health requirements (immunizations, check-ups).',
                    '✅ Education: Make sure school-age children have met the 85% attendance requirement.',
                    '✅ FDS: Attend the Family Development Session scheduled in your barangay.',
                    'Once all requirements are complete, visit the DSWD office to be verified by the Compliance Verifier.',
                    'After verification, you may claim your cash grant anytime within the quarter.',
                ],
                'actionUrl'  => config('app.url') . '/portal/dashboard',
                'actionText' => 'View My Portal',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'distribution_upcoming',
            'title'   => "📊 Quarter {$this->event->period} Opening Soon",
            'message' => "The {$this->event->period} quarter is going to be open soon. "
                . "Prepare your requirements now to be eligible for your cash grant. "
                . "Quarter runs from " . optional($this->event->period_start)->format('F d, Y')
                . " to " . optional($this->event->period_end)->format('F d, Y') . ".",
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
