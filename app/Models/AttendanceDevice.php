<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AttendanceDevice extends Model
{
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'last_ping_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $device) {
            if (empty($device->device_token)) {
                $device->device_token = 'DEV-' . Str::upper(Str::random(16));
            }
        });
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'device_id');
    }
}
