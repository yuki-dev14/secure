<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GrantUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $period,
        public float $totalAmount,
        public float $healthAmount = 0,
        public float $educationAmount = 0,
        public float $riceAmount = 0,
        public ?string $notes = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $formattedTotal = number_format($this->totalAmount, 2);
        $msg = "Your 4Ps bimonthly cash grant for period {$this->period} has been calculated and updated: Total ₱{$formattedTotal}.";
        if ($this->notes) {
            $msg .= " (Note: {$this->notes})";
        }

        return [
            'type'    => 'grant_updated',
            'title'   => "💰 Cash Grant Updated ({$this->period})",
            'message' => $msg,
            'details' => [
                'period'           => $this->period,
                'total_amount'     => $this->totalAmount,
                'health_amount'    => $this->healthAmount,
                'education_amount' => $this->educationAmount,
                'rice_amount'      => $this->riceAmount,
                'notes'            => $this->notes,
            ],
        ];
    }
}
