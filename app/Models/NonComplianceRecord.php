<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NonComplianceRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'beneficiary_id', 'family_member_id',
        'category', 'source',
        'reporter_name', 'reporter_institution',
        'reason', 'details',
        'period', 'period_start', 'period_end',
        'grant_affected',
        'status', 'processed_by', 'processed_at', 'processing_notes',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'period_start'  => 'date',
            'period_end'    => 'date',
            'processed_at'  => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeEducation($query)
    {
        return $query->where('category', 'education');
    }

    public function scopeHealth($query)
    {
        return $query->where('category', 'health');
    }

    // ─── Accessors ───────────────────────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'confirmed' => 'danger',
            'dismissed' => 'neutral',
            default     => 'neutral',
        };
    }

    public function getCategoryDisplayAttribute(): string
    {
        return match ($this->category) {
            'education' => 'Education',
            'health'    => 'Health & Nutrition',
            default     => ucfirst($this->category),
        };
    }

    public function getSourceDisplayAttribute(): string
    {
        return match ($this->source) {
            'school_rep' => 'School Representative',
            'midwife'    => 'Midwife',
            default      => ucfirst($this->source),
        };
    }
}
