<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return BelongsTo<AttendanceDevice, $this> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }
}
