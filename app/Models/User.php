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

    public function isSuperAdmin(): bool        { return $this->role === 'superadmin'; }
    public function isAdmin4ps(): bool         { return $this->role === 'admin_4ps'; }
    public function isAdminSwa(): bool         { return $this->role === 'admin_swa'; }
    public function isBarangayAssistant(): bool { return $this->role === 'barangay_assistant'; }
    public function isAdmin(): bool            { return in_array($this->role, ['admin', 'admin_4ps', 'admin_swa']); }
    public function isBeneficiary(): bool      { return $this->role === 'beneficiary'; }

    public function isStaff(): bool
    {
        return in_array($this->role, ['superadmin', 'admin', 'admin_4ps', 'admin_swa', 'barangay_assistant']);
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
        return $query->whereIn('role', ['superadmin', 'admin', 'admin_4ps', 'admin_swa', 'barangay_assistant']);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────────

    public function getRoleDisplayAttribute(): string
    {
        return match ($this->role) {
            'superadmin'          => 'Super Administrator',
            'admin_4ps'           => 'Admin (4Ps / FDS)',
            'admin_swa'           => 'Admin (SWA / Health & Education)',
            'barangay_assistant'  => 'Barangay Assistant (FDS)',
            'admin'               => 'Administrator',
            'beneficiary'         => 'Beneficiary',
            default               => ucfirst($this->role),
        };
    }

    public function getRoleBadgeColorAttribute(): string
    {
        return match ($this->role) {
            'superadmin'          => 'danger',
            'admin_4ps'           => 'info',
            'admin_swa'           => 'warning',
            'barangay_assistant'  => 'success',
            'admin'               => 'info',
            'beneficiary'         => 'neutral',
            default               => 'neutral',
        };
    }
}
