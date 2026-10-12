<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolGeofence extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
        'late_tolerance_minutes' => 'integer',
        'is_active' => 'boolean',
        'work_days' => 'array',
    ];
}
