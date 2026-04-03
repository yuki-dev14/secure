<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashGrantDistribution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'beneficiary_id', 'distribution_event_id', 'cash_grant_calculation_id',
        'transaction_reference', 'claimed_by_type', 'proxy_id',
        'amount_released', 'payment_mode', 'released_by',
        'status', 'verification_notes', 'claimed_at',
        'claimer_photo_path', 'qr_scan_event_id', 'device_info', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at'      => 'datetime',
            'amount_released' => 'decimal:2',
        ];
    }

    public function beneficiary(): BelongsTo      { return $this->belongsTo(Beneficiary::class); }
    public function distributionEvent(): BelongsTo { return $this->belongsTo(DistributionEvent::class); }
    public function calculation(): BelongsTo      { return $this->belongsTo(CashGrantCalculation::class, 'cash_grant_calculation_id'); }
    public function proxy(): BelongsTo            { return $this->belongsTo(Proxy::class); }
    public function releasedBy(): BelongsTo       { return $this->belongsTo(User::class, 'released_by'); }

    public function scopeClaimed($query)   { return $query->where('status', 'claimed'); }
    public function scopeUnclaimed($query) { return $query->where('status', 'unclaimed'); }

    public function isClaimed(): bool { return $this->status === 'claimed'; }
    public function isProxy(): bool   { return $this->claimed_by_type === 'proxy'; }
}
