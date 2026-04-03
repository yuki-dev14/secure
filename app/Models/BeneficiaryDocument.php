<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BeneficiaryDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'beneficiary_id', 'family_member_id', 'document_type', 'document_name',
        'file_path', 'file_type', 'file_size_kb', 'description',
        'is_verified', 'verified_by', 'verified_at', 'validity_date',
    ];

    protected function casts(): array
    {
        return [
            'validity_date' => 'date',
            'verified_at'   => 'datetime',
            'is_verified'   => 'boolean',
        ];
    }

    public function beneficiary(): BelongsTo  { return $this->belongsTo(Beneficiary::class); }
    public function familyMember(): BelongsTo { return $this->belongsTo(FamilyMember::class); }
    public function verifiedBy(): BelongsTo   { return $this->belongsTo(User::class, 'verified_by'); }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'birth_certificate'    => 'Birth Certificate',
            'valid_id'             => 'Valid ID',
            'school_id'            => 'School ID',
            'report_card'          => 'Report Card',
            'health_record'        => 'Health Record',
            'vaccination_booklet'  => 'Vaccination Booklet',
            'medical_certificate'  => 'Medical Certificate',
            'barangay_certificate' => 'Barangay Certificate',
            'photo_1x1'            => '1x1 Photo',
            'certificate_of_indigency' => 'Certificate of Indigency',
            'prenatal_record'      => 'Prenatal Record',
            default                => ucwords(str_replace('_', ' ', $this->document_type)),
        };
    }

    public function scopeVerified($query)   { return $query->where('is_verified', true); }
    public function scopeByType($query, string $type) { return $query->where('document_type', $type); }
}
