<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\BeneficiaryCard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BeneficiaryCardService
{
    public function __construct(
        private QrCodeService $qrService,
    ) {}

    /**
     * Create a new card record for a beneficiary, generate QR and PDF.
     * Returns the BeneficiaryCard model.
     * Card/PDF generation errors are non-fatal — the card record is always created.
     */
    public function issueCard(Beneficiary $beneficiary, ?int $issuedBy = null): BeneficiaryCard
    {
        // Deactivate any existing active cards
        $beneficiary->cards()->where('is_active', true)->update([
            'is_active'           => false,
            'deactivated_at'      => now(),
            'deactivation_reason' => 'New card issued',
        ]);

        // Generate credentials
        $qrPayload       = BeneficiaryCard::generateQrPayload($beneficiary->unique_id);
        $cardNumber      = BeneficiaryCard::generateCardNumber($beneficiary->unique_id);
        $defaultPassword = $this->generateDefaultPassword($beneficiary);

        /** @var BeneficiaryCard $card */
        $card = $beneficiary->cards()->create([
            'card_number'            => $cardNumber,
            'qr_code_data'           => $qrPayload,
            'default_password_hash'  => bcrypt($defaultPassword),
            'default_password_plain' => $defaultPassword,
            'is_active'              => true,
            'is_first_login'         => true,
            'issued_at'              => now(),
            'issued_by'              => $issuedBy ?? auth()->id(),
        ]);

        // Update user password if account exists
        if ($beneficiary->user) {
            $beneficiary->user->update([
                'password'             => bcrypt($defaultPassword),
                'must_change_password' => true,
            ]);
        }

        // Generate QR image — non-fatal (uses SVG, no imagick needed)
        try {
            $qrPath = $this->qrService->generate($card);
            $card->update(['qr_code_image_path' => $qrPath]);
        } catch (\Throwable $e) {
            Log::warning("QR generation failed for card {$card->card_number}: " . $e->getMessage());
        }

        // Generate PDF card — non-fatal
        try {
            $pdfPath = $this->generateCardPdf($beneficiary, $card, $defaultPassword);
            $beneficiary->update(['card_path' => $pdfPath]);
        } catch (\Throwable $e) {
            Log::warning("Card PDF generation failed for beneficiary {$beneficiary->unique_id}: " . $e->getMessage());
        }

        return $card;
    }

    /**
     * Generate a human-memorable default password.
     * Format: 4PS-[last4ofUniqueId]-[MMYY]
     */
    private function generateDefaultPassword(Beneficiary $beneficiary): string
    {
        $seq  = str_pad(Str::afterLast($beneficiary->unique_id, '-'), 4, '0', STR_PAD_LEFT);
        $mmyy = now()->format('mY');
        return "4PS-{$seq}-{$mmyy}";
    }

    /**
     * Render the ID card as a PDF and save to storage.
     * Returns the storage path.
     */
    private function generateCardPdf(Beneficiary $beneficiary, BeneficiaryCard $card, string $defaultPassword): string
    {
        // Embed QR — SVG as base64 data URI (works without imagick)
        $qrImageBase64 = '';
        if ($card->qr_code_image_path && Storage::disk('public')->exists($card->qr_code_image_path)) {
            $rawContent = Storage::disk('public')->get($card->qr_code_image_path);
            if (str_ends_with($card->qr_code_image_path, '.svg')) {
                $qrImageBase64 = 'data:image/svg+xml;base64,' . base64_encode($rawContent);
            } else {
                $qrImageBase64 = 'data:image/png;base64,' . base64_encode($rawContent);
            }
        }

        $photoBase64 = '';
        if ($beneficiary->photo_path && Storage::disk('public')->exists($beneficiary->photo_path)) {
            $photoRaw    = Storage::disk('public')->get($beneficiary->photo_path);
            $ext         = pathinfo($beneficiary->photo_path, PATHINFO_EXTENSION);
            $photoBase64 = "data:image/{$ext};base64," . base64_encode($photoRaw);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.beneficiary-card', [
            'beneficiary'     => $beneficiary,
            'card'            => $card,
            'defaultPassword' => $defaultPassword,
            'qrImageBase64'   => $qrImageBase64,
            'photoBase64'     => $photoBase64,
        ])->setPaper([0, 0, 241.89, 153.07], 'landscape'); // CR80 card size in points

        $filename = 'cards/card_' . $card->card_number . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }
}
