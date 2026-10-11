<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleName;
use App\Traits\HasObfuscatedId;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, HasObfuscatedId;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
        'pin_hash',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([
            RoleName::SuperAdmin->value,
            RoleName::Admin->value,
            RoleName::Headmaster->value,
            RoleName::Treasurer->value,
            RoleName::Staff->value,
            RoleName::Operator->value,
        ]);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'lockout_until' => 'datetime',
        ];
    }

    // ─── Relasi Data ──────────────────────────────────────────────
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'parent_user_id');
    }

    public function auditTrails(): HasMany
    {
        return $this->hasMany(AuditTrail::class);
    }

    // ─── Keamanan Akun & Proteksi Brute-force ──────────────────────
    public function isLockedOut(): bool
    {
        if ($this->is_locked && $this->lockout_until && $this->lockout_until->isFuture()) {
            return true;
        }

        if ($this->is_locked && $this->lockout_until && $this->lockout_until->isPast()) {
            $this->unlockAccount();
            return false;
        }

        return (bool) $this->is_locked;
    }

    public function lockAccount(string $reason = 'Percobaan login gagal berulang kali', int $minutes = 30): void
    {
        $this->update([
            'is_locked' => true,
            'lockout_until' => now()->addMinutes($minutes),
            'lockout_reason' => $reason,
        ]);
    }

    public function unlockAccount(): void
    {
        $this->update([
            'is_locked' => false,
            'lockout_until' => null,
            'lockout_reason' => null,
        ]);
    }

    // ─── Keamanan PIN 6-Digit Dompet Digital ───────────────────────
    public function verifyPin(string $pin): bool
    {
        if (!$this->pin_hash) {
            return false;
        }

        return Hash::check($pin, $this->pin_hash);
    }

    public function setPin(string $pin): void
    {
        $this->update([
            'pin_hash' => Hash::make($pin),
        ]);
    }
}
