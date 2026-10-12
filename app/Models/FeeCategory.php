<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCategory extends Model
{
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'is_recurring' => 'boolean',
        'default_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return HasMany<StudentBill, $this> */
    public function bills(): HasMany
    {
        return $this->hasMany(StudentBill::class);
    }
}
