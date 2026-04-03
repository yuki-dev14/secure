<?php

namespace App\Notifications;

use App\Models\DistributionEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DistributionScheduleNotification extends Notification
{
    use Queueable;

    public function __construct(public DistributionEvent $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
                'date_start'  => $this->event->distribution_date_start->format('F d, Y'),
                'date_end'    => $this->event->distribution_date_end->format('F d, Y'),
                'time_start'  => $this->event->distribution_time_start,
            ],
        ];
    }
}
