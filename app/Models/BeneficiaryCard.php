<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BeneficiaryCard extends Model
{
    protected $fillable = [
        'beneficiary_id', 'card_number', 'qr_code_data', 'qr_code_image_path',
        'default_password_hash', 'default_password_plain',
        'is_active', 'is_first_login', 'first_login_at',
        'password_changed_at', 'issued_at', 'issued_by',
        'deactivated_at', 'deactivation_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'is_first_login'     => 'boolean',
            'first_login_at'     => 'datetime',
            'password_changed_at'=> 'datetime',
            'issued_at'          => 'datetime',
            'deactivated_at'     => 'datetime',
        ];
    }

    public function beneficiary(): BelongsTo { return $this->belongsTo(Beneficiary::class); }
    public function issuedBy(): BelongsTo    { return $this->belongsTo(User::class, 'issued_by'); }

    /**
     * Generate QR Code data payload.
     * Encodes: unique_id + timestamp token for verification.
     */
    public static function generateQrPayload(string $uniqueId): string
    {
        return base64_encode(json_encode([
            'uid'   => $uniqueId,
            'sys'   => 'SECURE-4PS',
            'city'  => 'LIPA',
            'token' => Str::random(16),
        ]));
    }

    /**
     * Generate card number: CARD-LPA-XXXXXX-YY
     */
    public static function generateCardNumber(string $uniqueId): string
    {
        $seq    = Str::afterLast($uniqueId, '-');
        $suffix = strtoupper(Str::random(2));
        return "CARD-LPA-{$seq}-{$suffix}";
    }
}
