<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'username', 'password',
        'role', 'office_id', 'employee_id',
        'contact_number', 'position', 'is_active',
        'must_change_password', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['role_display'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    // ─── Role Helpers ────────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool  { return $this->role === 'superadmin'; }
    public function isAdmin(): bool        { return $this->role === 'admin'; }
    public function isVerifier(): bool     { return $this->role === 'compliance_verifier'; }
    public function isFieldOfficer(): bool { return $this->role === 'field_officer'; }
    public function isBeneficiary(): bool  { return $this->role === 'beneficiary'; }

    public function isStaff(): bool
    {
        return in_array($this->role, ['superadmin', 'admin', 'compliance_verifier', 'field_officer']);
    }

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function beneficiary(): HasOne
    {
        return $this->hasOne(Beneficiary::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('role', ['superadmin', 'admin', 'compliance_verifier', 'field_officer']);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────────

    public function getRoleDisplayAttribute(): string
    {
        return match ($this->role) {
            'superadmin'          => 'Super Administrator',
            'admin'               => 'Administrator',
            'compliance_verifier' => 'Compliance Verifier',
            'field_officer'       => 'Field Officer',
            'beneficiary'         => 'Beneficiary',
            default               => ucfirst($this->role),
        };
    }

    public function getRoleBadgeColorAttribute(): string
    {
        return match ($this->role) {
            'superadmin'          => 'danger',
            'admin'               => 'info',
            'compliance_verifier' => 'warning',
            'field_officer'       => 'success',
            'beneficiary'         => 'neutral',
            default               => 'neutral',
        };
    }
}
