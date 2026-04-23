<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Beneficiary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'unique_id', 'listahanan_id',
        'household_head_name', 'first_name', 'last_name', 'middle_name', 'suffix',
        'birthdate', 'sex', 'civil_status', 'contact_number',
        'house_no', 'street', 'purok', 'barangay', 'city', 'province', 'zip_code',
        'office_id', 'status', 'enrollment_date', 'graduation_date', 'remarks',
        'photo_path', 'card_path',
        'is_compliant', 'last_compliance_check', 'created_by',
    ];

    protected $appends = ['full_name', 'full_address', 'age', 'status_color'];

    protected function casts(): array
    {
        return [
            'birthdate'              => 'date',
            'enrollment_date'        => 'date',
            'graduation_date'        => 'date',
            'last_compliance_check'  => 'datetime',
            'is_compliant'           => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function card(): HasOne
    {
        return $this->hasOne(BeneficiaryCard::class)->latestOfMany();
    }

    public function cards(): HasMany
    {
        return $this->hasMany(BeneficiaryCard::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class)->where('is_active', true);
    }

    public function allFamilyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function proxies(): HasMany
    {
        return $this->hasMany(Proxy::class)->where('is_active', true);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BeneficiaryDocument::class);
    }

    public function complianceRecords(): HasMany
    {
        return $this->hasMany(ComplianceRecord::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(CashGrantDistribution::class);
    }

    public function grantCalculations(): HasMany
    {
        return $this->hasMany(CashGrantCalculation::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompliant($query)
    {
        return $query->where('is_compliant', true);
    }

    public function scopeInBarangay($query, string $barangay)
    {
        return $query->where('barangay', $barangay);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name ? Str::upper(Str::substr($this->middle_name, 0, 1)).'.' : null,
            $this->last_name,
            $this->suffix,
        ]);
        return implode(' ', $parts);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->house_no,
            $this->street,
            $this->purok ? 'Purok '.$this->purok : null,
            'Brgy. '.$this->barangay,
            $this->city,
            $this->province,
        ]);
        return implode(', ', $parts);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthdate?->age;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'success',
            'inactive'  => 'neutral',
            'suspended' => 'danger',
            'graduated' => 'info',
            'delisted'  => 'danger',
            default     => 'neutral',
        };
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Generate the next unique ID for a beneficiary in Lipa City.
     * Format: 4PS-LPA-XXXXXX (zero-padded 6-digit sequence)
     */
    public static function generateUniqueId(): string
    {
        $lastId = static::withTrashed()
            ->where('unique_id', 'like', '4PS-LPA-%')
            ->orderByDesc('id')
            ->value('unique_id');

        $next = $lastId ? (int) Str::afterLast($lastId, '-') + 1 : 1;

        return '4PS-LPA-'.str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get eligible children for education grant (max 3).
     * Returns collection of children sorted by grant amount DESC (highest first).
     */
    public function educationGrantEligibleChildren()
    {
        return $this->familyMembers()
            ->whereIn('education_level', ['elementary', 'junior_high', 'senior_high'])
            ->where('is_school_age', true)
            ->where('edu_attendance_compliant', true)
            ->orderByRaw("CASE education_level
                WHEN 'senior_high' THEN 1
                WHEN 'junior_high' THEN 2
                WHEN 'elementary' THEN 3
                ELSE 4 END")
            ->limit(3)
            ->get();
    }
}
