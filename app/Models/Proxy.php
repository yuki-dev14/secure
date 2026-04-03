<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proxy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'beneficiary_id', 'first_name', 'last_name', 'middle_name', 'suffix',
        'birthdate', 'sex', 'relationship', 'contact_number', 'address',
        'valid_id_path', 'birth_certificate_path', 'valid_id_type', 'valid_id_number',
        'is_approved', 'approved_by', 'approved_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birthdate'   => 'date',
            'approved_at' => 'datetime',
            'is_approved' => 'boolean',
            'is_active'   => 'boolean',
        ];
    }

    public function beneficiary(): BelongsTo  { return $this->belongsTo(Beneficiary::class); }
    public function approvedBy(): BelongsTo   { return $this->belongsTo(User::class, 'approved_by'); }

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([
            $this->first_name, $this->last_name, $this->suffix,
        ]));
    }

    public function hasRequiredDocuments(): bool
    {
        return !empty($this->valid_id_path) && !empty($this->birth_certificate_path);
    }

    public function scopeApproved($query)    { return $query->where('is_approved', true); }
    public function scopeActive($query)      { return $query->where('is_active', true); }
}
