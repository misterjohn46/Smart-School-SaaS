<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /** @return HasMany<Classroom, $this> */
    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
