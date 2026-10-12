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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string|null $nisn
 * @property string $email
 * @property string|null $phone_number
 * @property string $password
 * @property string|null $pin_hash
 * @property Carbon|null $email_verified_at
 * @property bool $is_active
 * @property bool $is_locked
 * @property Carbon|null $lockout_until
 * @property string|null $lockout_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasObfuscatedId, HasRoles, Notifiable;

    /**
     * Daftar atribut yang boleh diisi massal.
     *
     * Sebelumnya `$guarded = []` membuat `is_active`, `is_locked`, `lockout_until`,
     * dan `pin_hash` dapat ditimpa lewat satu pemanggilan update($request->all()).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'nisn',
        'email',
        'phone_number',
        'password',
        'email_verified_at',
        'is_active',
        'is_locked',
    ];

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

    /** @return HasOne<TeacherProfile, $this> */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /** @return HasOne<StudentProfile, $this> */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /** @return HasMany<StudentProfile, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'parent_user_id');
    }

    /** @return HasMany<AuditTrail, $this> */
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
        // forceFill dipakai karena lockout_until & lockout_reason sengaja tidak
        // masuk $fillable: statusnya hanya boleh ditulis oleh logika keamanan ini.
        $this->forceFill([
            'is_locked' => true,
            'lockout_until' => now()->addMinutes($minutes),
            'lockout_reason' => $reason,
        ])->save();
    }

    public function unlockAccount(): void
    {
        $this->forceFill([
            'is_locked' => false,
            'lockout_until' => null,
            'lockout_reason' => null,
        ])->save();
    }

    // ─── Keamanan PIN 6-Digit Dompet Digital ───────────────────────
    public function verifyPin(string $pin): bool
    {
        if (! $this->pin_hash) {
            return false;
        }

        return Hash::check($pin, $this->pin_hash);
    }

    public function setPin(string $pin): void
    {
        $this->forceFill([
            'pin_hash' => Hash::make($pin),
        ])->save();
    }
}
