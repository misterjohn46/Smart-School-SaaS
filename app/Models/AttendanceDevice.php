<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AttendanceDevice extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    /**
     * Daftar atribut yang boleh diisi massal. `device_token` sengaja disertakan
     * karena merupakan atribut virtual yang dialihkan ke kolom hash oleh mutator.
     *
     * @var list<string>
     */
    protected $fillable = [
        'device_token',
        'device_name',
        'location',
        'device_type',
        'ip_address',
        'last_ping_at',
        'is_active',
    ];

    protected $casts = [
        'last_ping_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Token plaintext. Hanya terisi pada instance yang baru membuat / meregenerasi
     * token, agar bisa ditampilkan sekali ke operator untuk di-flash ke ESP32.
     */
    protected ?string $plainTextToken = null;

    protected static function booted(): void
    {
        static::creating(function (self $device) {
            if (empty($device->device_token_hash)) {
                $device->device_token = static::generateToken();
            }
        });
    }

    public static function generateToken(): string
    {
        return 'DEV-'.Str::upper(Str::random(32));
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Cari perangkat berdasarkan token plaintext yang dikirim mikrokontroler.
     *
     * Pencarian dilakukan atas hash, sehingga token asli tidak pernah dibandingkan
     * maupun disimpan dalam bentuk yang dapat dibaca ulang.
     */
    public static function findByToken(?string $token): ?self
    {
        if ($token === null || $token === '') {
            return null;
        }

        return static::query()->where('device_token_hash', static::hashToken($token))->first();
    }

    /**
     * Mutator atribut virtual: menerima token plaintext, menyimpan hash + preview.
     */
    public function setDeviceTokenAttribute(string $token): void
    {
        $this->plainTextToken = $token;
        $this->attributes['device_token_hash'] = static::hashToken($token);
        $this->attributes['device_token_preview'] = mb_substr($token, 0, 6).'••••';
    }

    public function getDeviceTokenAttribute(): ?string
    {
        return $this->plainTextToken;
    }

    /**
     * Terbitkan token baru dan kembalikan plaintext-nya satu kali.
     */
    public function regenerateToken(): string
    {
        $token = static::generateToken();
        $this->device_token = $token;
        $this->save();

        return $token;
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'device_id');
    }
}
