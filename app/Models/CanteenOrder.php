<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenOrder extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'order_date' => 'datetime',
    ];

    /** @return BelongsTo<CanteenStand, $this> */
    public function stand(): BelongsTo
    {
        return $this->belongsTo(CanteenStand::class, 'canteen_stand_id');
    }

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CanteenOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CanteenOrderItem::class);
    }
}
