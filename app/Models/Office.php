<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'type', 'address', 'barangay',
        'city', 'province', 'contact_number', 'officer_in_charge', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): HasMany   { return $this->hasMany(User::class); }
    public function beneficiaries(): HasMany { return $this->hasMany(Beneficiary::class); }
    public function distributionEvents(): HasMany { return $this->hasMany(DistributionEvent::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
