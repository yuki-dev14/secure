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
            ->subject("📅 Cash Grant Available — {$this->event->period} | SECURE 4Ps")
            ->view('emails.distribution-schedule', [
                'subject'      => "Cash Grant Available — {$this->event->period}",
                'greeting'     => "Hello, {$name}!",
                'introLine'    => 'Your 4Ps cash grant is now available for claiming. Please proceed to the distribution venue on the scheduled date with your SECURE 4Ps ID card.',
                'alertType'    => 'info',
                'detailsTitle' => '📅 Distribution Schedule',
                'details'      => [
                    'Period'           => $this->event->period,
                    'Event Name'       => $this->event->title,
                    'Venue'            => $this->event->venue ?? 'To be announced',
                    'Date Start'       => $this->event->distribution_date_start?->format('F d, Y') ?? '—',
                    'Date End'         => $this->event->distribution_date_end?->format('F d, Y') ?? '—',
                    'Time'             => $this->event->distribution_time_start ?? 'As scheduled',
                ],
                'noteLines'    => [
                    '⚠ Important: Bring your SECURE 4Ps ID card and one (1) valid government-issued ID.',
                    'If you cannot claim personally, your registered authorized proxy may claim on your behalf.',
                    'Unclaimed grants within the distribution period may require a separate request for re-scheduling.',
                ],
                'actionUrl'    => config('app.url') . '/portal/dashboard',
                'actionText'   => 'View My Portal',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'distribution_schedule',
            'title'   => '📅 Cash Grant Distribution Scheduled',
            'message' => "Your cash grant is now available for claiming. {$this->event->title}",
            'details' => [
                'event_id'    => $this->event->id,
                'period'      => $this->event->period,
                'venue'       => $this->event->venue,
                'date_start'  => $this->event->distribution_date_start?->format('F d, Y'),
                'date_end'    => $this->event->distribution_date_end?->format('F d, Y'),
                'time_start'  => $this->event->distribution_time_start,
            ],
        ];
    }
}
