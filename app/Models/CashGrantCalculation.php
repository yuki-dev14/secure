<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashGrantCalculation extends Model
{
    protected $fillable = [
        'beneficiary_id', 'distribution_event_id', 'months_covered',
        'is_eligible', 'ineligibility_reason',
        'health_grant_eligible', 'health_grant_amount',
        'elementary_children_count', 'elementary_grant_amount',
        'junior_high_children_count', 'junior_high_grant_amount',
        'senior_high_children_count', 'senior_high_grant_amount',
        'education_grant_total',
        'rice_subsidy_eligible', 'rice_subsidy_amount',
        'total_grant_amount', 'compute_status',
        'computed_by', 'computed_at', 'computation_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_eligible'             => 'boolean',
            'health_grant_eligible'   => 'boolean',
            'rice_subsidy_eligible'   => 'boolean',
            'health_grant_amount'     => 'decimal:2',
            'elementary_grant_amount' => 'decimal:2',
            'junior_high_grant_amount'=> 'decimal:2',
            'senior_high_grant_amount'=> 'decimal:2',
            'education_grant_total'   => 'decimal:2',
            'rice_subsidy_amount'     => 'decimal:2',
            'total_grant_amount'      => 'decimal:2',
            'computed_at'             => 'datetime',
        ];
    }

    public function beneficiary(): BelongsTo      { return $this->belongsTo(Beneficiary::class); }
    public function distributionEvent(): BelongsTo { return $this->belongsTo(DistributionEvent::class); }
    public function computedBy(): BelongsTo       { return $this->belongsTo(User::class, 'computed_by'); }
    public function distribution(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CashGrantDistribution::class);
    }
}
