<?php
// Seed a test cash grant distribution (receipt) for the existing event and beneficiary

$officer = App\Models\User::where('role', 'field_officer')->first();
$event   = App\Models\DistributionEvent::first();
$beneficiary = App\Models\Beneficiary::first();

if (!$officer || !$event || !$beneficiary) {
    echo "Missing: officer={$officer?->id}, event={$event?->id}, beneficiary={$beneficiary?->id}\n";
    exit;
}

$txnRef = 'TXN-TEST-' . now()->format('YmdHis');

$dist = App\Models\CashGrantDistribution::create([
    'beneficiary_id'        => $beneficiary->id,
    'distribution_event_id' => $event->id,
    'transaction_reference' => $txnRef,
    'claimed_by_type'       => 'beneficiary',
    'amount_released'       => 2700.00,
    'payment_mode'          => 'cash',
    'released_by'           => $officer->id,
    'status'                => 'claimed',
    'claimed_at'            => now(),
    'verification_notes'    => 'Valid ID presented. QR card verified. No irregularities.',
    'ip_address'            => '127.0.0.1',
]);

echo "Distribution created: {$txnRef} (ID: {$dist->id})\n";
echo "Receipt URL: http://127.0.0.1:8000/officer/distribution/{$txnRef}\n";
