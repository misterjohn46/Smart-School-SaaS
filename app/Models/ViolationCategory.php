<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationCategory extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'points' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @return HasMany<StudentViolation, $this> */
    public function studentViolations(): HasMany
    {
        return $this->hasMany(StudentViolation::class);
    }

    public function scopePelanggaran(Builder $query): Builder
    {
        return $query->where('type', 'pelanggaran');
    }

    public function scopePrestasi(Builder $query): Builder
    {
        return $query->where('type', 'prestasi');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
