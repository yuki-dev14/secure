<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyMember extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'beneficiary_id', 'first_name', 'last_name', 'middle_name', 'suffix',
        'birthdate', 'sex', 'relationship', 'is_household_head',
        'is_school_age', 'education_level', 'school_name',
        'grade_level', 'lrn', 'attendance_rate',
        'is_under_five', 'is_fully_immunized', 'last_weighed_at',
        'weight_kg', 'height_cm', 'nutritional_status',
        'is_pregnant', 'expected_delivery_date',
        'prenatal_compliant', 'postnatal_compliant', 'professional_delivery',
        'is_active', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'birthdate'             => 'date',
            'last_weighed_at'       => 'date',
            'expected_delivery_date'=> 'date',
            'is_household_head'     => 'boolean',
            'is_school_age'         => 'boolean',
            'is_under_five'         => 'boolean',
            'is_fully_immunized'    => 'boolean',
            'is_pregnant'           => 'boolean',
            'prenatal_compliant'    => 'boolean',
            'postnatal_compliant'   => 'boolean',
            'professional_delivery' => 'boolean',
            'is_active'             => 'boolean',
            'weight_kg'             => 'decimal:2',
            'height_cm'             => 'decimal:2',
            'attendance_rate'       => 'decimal:2',
        ];
    }

    public function beneficiary(): BelongsTo { return $this->belongsTo(Beneficiary::class); }
    public function documents(): HasMany      { return $this->hasMany(BeneficiaryDocument::class); }
    public function complianceRecords(): HasMany { return $this->hasMany(ComplianceRecord::class); }

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([
            $this->first_name, $this->last_name, $this->suffix
        ]));
    }

    public function getAgeAttribute(): int  { return $this->birthdate->age; }

    public function getEducationGrantAmountAttribute(): int
    {
        return match ($this->education_level) {
            'elementary'  => 300,
            'junior_high' => 500,
            'senior_high' => 700,
            default       => 0,
        };
    }

    public function getAttendanceCompliantAttribute(): bool
    {
        return $this->attendance_rate !== null && $this->attendance_rate >= 85.0;
    }

    public function scopeSchoolAge($query)   { return $query->where('is_school_age', true); }
    public function scopeUnderFive($query)   { return $query->where('is_under_five', true); }
    public function scopePregnant($query)    { return $query->where('is_pregnant', true); }
    public function scopeActive($query)      { return $query->where('is_active', true); }
}
