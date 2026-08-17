<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceVerificationBatch extends Model
{
    protected $fillable = [
        'period', 'category',
        'recipient_email', 'recipient_name',
        'beneficiary_count', 'non_compliant_count',
        'sent_by', 'sent_at',
        'imported_by', 'imported_at',
        'status', 'file_path',
    ];

    protected function casts(): array
    {
        return [
            'sent_at'     => 'datetime',
            'imported_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeEducation($query)
    {
        return $query->where('category', 'education');
    }

    public function scopeHealth($query)
    {
        return $query->where('category', 'health');
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────────

    public function getCategoryDisplayAttribute(): string
    {
        return match ($this->category) {
            'education' => 'Education',
            'health'    => 'Health & Nutrition',
            default     => ucfirst($this->category),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'sent'     => 'warning',
            'imported' => 'success',
            default    => 'neutral',
        };
    }
}
