<?php

namespace App\Notifications;

use App\Models\BeneficiaryCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CardIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BeneficiaryCard $card) {}

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
            ->subject('🪪 Your SECURE 4Ps ID Card Has Been Issued')
            ->view('emails.distribution-schedule', [
                'subject'      => 'Your SECURE 4Ps ID Card Has Been Issued',
                'greeting'     => "Hello, {$name}!",
                'introLine'    => 'Your 4Ps ID card has been issued by the DSWD office. Please claim your physical card at your assigned Barangay Social Welfare Center.',
                'alertType'    => 'success',
                'detailsTitle' => '🪪 Card Details',
                'details'      => [
                    'Card Number'      => $this->card->card_number,
                    'Issued On'        => $this->card->issued_at?->format('F d, Y') ?? now()->format('F d, Y'),
                    'Status'           => 'Active',
                    'Default Password' => $this->card->default_password_plain ?? '(See card back)',
                ],
                'noteLines'    => [
                    '🔐 Your default portal password is printed on the back of your physical card.',
                    'You will be required to change your password on your first login at ' . config('app.url') . '/portal.',
                    '⚠ Keep your card safe. Report lost or stolen cards immediately to your BSWC.',
                ],
                'actionUrl'    => config('app.url') . '/portal',
                'actionText'   => 'Login to My Portal',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'card_issued',
            'title'   => '🪪 Your ID Card Has Been Issued',
            'message' => "Your SECURE 4Ps ID card ({$this->card->card_number}) has been issued. Claim it at your BSWC.",
            'details' => [
                'card_number' => $this->card->card_number,
                'issued_at'   => $this->card->issued_at?->format('F d, Y'),
            ],
        ];
    }
}
