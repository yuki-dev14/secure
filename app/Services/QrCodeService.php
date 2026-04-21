<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\BeneficiaryCard;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    const QR_SIZE    = 300;
    const QR_MARGIN  = 1;
    const STORAGE_DIR = 'qrcodes';

    /**
     * Generate a QR code image for a beneficiary card and save to storage.
     * Returns the storage path.
     */
    public function generate(BeneficiaryCard $card): string
    {
        $payload  = $card->qr_code_data;
        $filename = 'qr_'.$card->card_number.'_'.time().'.svg';
        $path     = self::STORAGE_DIR.'/'.$filename;

        // Use SVG format — works with GD or no image driver (no imagick required)
        $svgData = QrCode::format('svg')
            ->size(self::QR_SIZE)
            ->margin(self::QR_MARGIN)
            ->errorCorrection('H')
            ->generate($payload);

        \Storage::disk('public')->put($path, $svgData);

        return $path;
    }

    /**
     * Decode and validate a QR code payload scanned by the field officer.
     * Returns ['valid' => bool, 'beneficiary' => Beneficiary|null, 'error' => string|null]
     */
    public function decode(string $rawPayload): array
    {
        try {
            $decoded = json_decode(base64_decode($rawPayload), true);

            if (!$decoded || !isset($decoded['uid'], $decoded['sys'], $decoded['city'], $decoded['token'])) {
                return ['valid' => false, 'beneficiary' => null, 'error' => 'Invalid QR code format.'];
            }

            if ($decoded['sys'] !== 'SECURE-4PS') {
                return ['valid' => false, 'beneficiary' => null, 'error' => 'QR code does not belong to this system.'];
            }

            if ($decoded['city'] !== 'LIPA') {
                return ['valid' => false, 'beneficiary' => null, 'error' => 'QR code is not registered for Lipa City.'];
            }

            // Find by unique_id
            $beneficiary = Beneficiary::where('unique_id', $decoded['uid'])
                ->with(['card', 'familyMembers', 'proxies', 'documents', 'user'])
                ->first();

            if (!$beneficiary) {
                return ['valid' => false, 'beneficiary' => null, 'error' => 'Beneficiary not found in the system.'];
            }

            if ($beneficiary->status !== 'active') {
                return ['valid' => false, 'beneficiary' => $beneficiary, 'error' => "Beneficiary account is {$beneficiary->status}."];
            }

            // Validate the card QR data matches by comparing tokens
            $activeCard = $beneficiary->cards()->where('is_active', true)->first();
            if (!$activeCard) {
                return ['valid' => false, 'beneficiary' => $beneficiary, 'error' => 'No active card found for this beneficiary.'];
            }

            // Decode the stored payload to extract its token for comparison
            $storedDecoded = json_decode(base64_decode($activeCard->qr_code_data), true);
            $scannedToken  = $decoded['token'] ?? null;
            $storedToken   = $storedDecoded['token'] ?? null;

            if (!$scannedToken || !$storedToken || $scannedToken !== $storedToken) {
                return ['valid' => false, 'beneficiary' => $beneficiary, 'error' => 'QR code does not match the active card on record.'];
            }

            return ['valid' => true, 'beneficiary' => $beneficiary, 'card' => $activeCard, 'error' => null];

        } catch (\Throwable $e) {
            return ['valid' => false, 'beneficiary' => null, 'error' => 'QR code processing failed: '.$e->getMessage()];
        }
    }

    /**
     * Decode a QR payload for beneficiary self-login (portal access).
     */
    public function decodeForLogin(string $rawPayload): ?string
    {
        try {
            $decoded = json_decode(base64_decode($rawPayload), true);
            return $decoded['uid'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
