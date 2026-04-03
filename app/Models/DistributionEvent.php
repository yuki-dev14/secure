<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributionEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'period', 'period_start', 'period_end', 'months_covered',
        'distribution_date_start', 'distribution_date_end',
        'distribution_time_start', 'distribution_time_end',
        'office_id', 'venue', 'venue_address', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start'              => 'date',
            'period_end'                => 'date',
            'distribution_date_start'   => 'date',
            'distribution_date_end'     => 'date',
        ];
    }

    public function office(): BelongsTo     { return $this->belongsTo(Office::class); }
    public function creator(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function distributions(): HasMany { return $this->hasMany(CashGrantDistribution::class); }
    public function calculations(): HasMany { return $this->hasMany(CashGrantCalculation::class); }

    public function scopeUpcoming($query)   { return $query->where('status', 'upcoming'); }
    public function scopeOngoing($query)    { return $query->where('status', 'ongoing'); }
    public function scopeCompleted($query)  { return $query->where('status', 'completed'); }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'upcoming'  => 'info',
            'ongoing'   => 'success',
            'completed' => 'neutral',
            'cancelled' => 'danger',
            default     => 'neutral',
        };
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['upcoming', 'ongoing']);
    }
}
