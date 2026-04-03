<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'beneficiary_id', 'family_member_id', 'verified_by', 'period',
        'period_start', 'period_end',
        'edu_enrolled', 'edu_attendance_rate', 'edu_attendance_compliant',
        'health_immunization_complete', 'health_weight_monitored',
        'health_last_checkup', 'health_compliant',
        'pregnancy_prenatal_compliant', 'pregnancy_postnatal_compliant',
        'pregnancy_professional_delivery', 'pregnancy_compliant',
        'fds_attended', 'fds_date', 'fds_venue', 'fds_compliant',
        'is_fully_compliant', 'remarks', 'non_compliance_reasons',
    ];

    protected function casts(): array
    {
        return [
            'period_start'                  => 'date',
            'period_end'                    => 'date',
            'health_last_checkup'           => 'date',
            'fds_date'                      => 'date',
            'edu_enrolled'                  => 'boolean',
            'edu_attendance_compliant'      => 'boolean',
            'health_immunization_complete'  => 'boolean',
            'health_weight_monitored'       => 'boolean',
            'health_compliant'              => 'boolean',
            'pregnancy_prenatal_compliant'  => 'boolean',
            'pregnancy_postnatal_compliant' => 'boolean',
            'pregnancy_professional_delivery' => 'boolean',
            'pregnancy_compliant'           => 'boolean',
            'fds_attended'                  => 'boolean',
            'fds_compliant'                 => 'boolean',
            'is_fully_compliant'            => 'boolean',
            'edu_attendance_rate'           => 'decimal:2',
        ];
    }

    public function beneficiary(): BelongsTo  { return $this->belongsTo(Beneficiary::class); }
    public function familyMember(): BelongsTo { return $this->belongsTo(FamilyMember::class); }
    public function verifier(): BelongsTo     { return $this->belongsTo(User::class, 'verified_by'); }

    public function scopeCompliant($query)    { return $query->where('is_fully_compliant', true); }
    public function scopeForPeriod($query, string $period) { return $query->where('period', $period); }
}
