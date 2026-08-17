<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FdsAttendance extends Model
{
    protected $table = 'fds_attendance';

    protected $fillable = [
        'beneficiary_id',
        'session_title', 'period', 'period_start', 'period_end',
        'session_date', 'venue',
        'qr_verified', 'scanned_at', 'scanned_device',
        'checked_in_at', 'checked_in_device',
        'checked_out_at', 'checked_out_device',
        'is_complete',
        'is_reported', 'reported_at', 'reported_by',
        'recorded_by', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'period_start'    => 'date',
            'period_end'      => 'date',
            'session_date'    => 'date',
            'scanned_at'      => 'datetime',
            'checked_in_at'   => 'datetime',
            'checked_out_at'  => 'datetime',
            'reported_at'     => 'datetime',
            'qr_verified'     => 'boolean',
            'is_complete'     => 'boolean',
            'is_reported'     => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeVerified($query)
    {
        return $query->where('qr_verified', true);
    }

    public function scopeComplete($query)
    {
        return $query->where('is_complete', true);
    }

    public function scopeIncomplete($query)
    {
        return $query->where('is_complete', false);
    }

    public function scopeReported($query)
    {
        return $query->where('is_reported', true);
    }

    public function scopeUnreported($query)
    {
        return $query->where('is_reported', false);
    }
}
