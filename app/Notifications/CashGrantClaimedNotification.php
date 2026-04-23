<?php

namespace App\Notifications;

use App\Models\CashGrantDistribution;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a beneficiary (via their User account) immediately after a
 * field officer records a successful cash grant claim.
 */
class CashGrantClaimedNotification extends Notification
{
    use Queueable;

    public function __construct(public CashGrantDistribution $distribution) {}

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
        $amount      = number_format($this->distribution->amount_released, 2);
        $period      = $this->distribution->distributionEvent?->period ?? '—';
        $claimedBy   = $this->distribution->claimed_by_type === 'proxy'
            ? ($this->distribution->proxy?->full_name ?? 'Authorized Proxy')
            : $name;

        return (new MailMessage)
            ->subject("✅ Cash Grant Claimed — {$period} | SECURE 4Ps")
            ->view('emails.distribution-schedule', [
                'subject'      => "Cash Grant Successfully Claimed — {$period}",
                'greeting'     => "Hello, {$name}!",
                'introLine'    => "Your cash grant for the period {$period} has been successfully released. "
                    . "The amount of ₱{$amount} was claimed at the DSWD distribution center.",
                'alertType'    => 'success',
                'detailsTitle' => '✅ Claim Receipt',
                'details'      => [
                    'Transaction Ref'  => $this->distribution->transaction_reference,
                    'Period'           => $period,
                    'Amount Released'  => "₱{$amount}",
                    'Claimed By'       => $claimedBy,
                    'Date & Time'      => $this->distribution->claimed_at?->format('F d, Y g:i A') ?? now()->format('F d, Y g:i A'),
                    'Payment Mode'     => ucfirst($this->distribution->payment_mode ?? 'cash'),
                ],
                'noteLines' => [
                    '🔐 Keep this reference number for your records: ' . $this->distribution->transaction_reference,
                    'If you did not personally claim this grant or believe this was issued in error, please report immediately to your Barangay Social Welfare Center.',
                    'Continue meeting your co-responsibilities to remain eligible for the next quarter.',
                ],
                'actionUrl'  => config('app.url') . '/portal/dashboard',
                'actionText' => 'View My Dashboard',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $period  = $this->distribution->distributionEvent?->period ?? '—';
        $amount  = number_format($this->distribution->amount_released, 2);
        $claimBy = $this->distribution->claimed_by_type === 'proxy'
            ? ($this->distribution->proxy?->full_name ?? 'Proxy')
            : 'Self';

        return [
            'type'    => 'cash_grant_claimed',
            'title'   => "✅ Cash Grant Claimed — {$period}",
            'message' => "Your cash grant of ₱{$amount} for {$period} has been successfully released. "
                . "Claimed by: {$claimBy}.",
            'details' => [
                'transaction_ref'  => $this->distribution->transaction_reference,
                'period'           => $period,
                'amount'           => $this->distribution->amount_released,
                'claimed_by_type'  => $this->distribution->claimed_by_type,
                'claimed_by_name'  => $claimBy,
                'payment_mode'     => $this->distribution->payment_mode,
                'claimed_at'       => $this->distribution->claimed_at?->format('F d, Y g:i A'),
                'event_id'         => $this->distribution->distribution_event_id,
            ],
        ];
    }
}
