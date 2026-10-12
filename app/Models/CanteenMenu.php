<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenMenu extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock' => 'integer',
        'is_available' => 'boolean',
    ];

    /** @return BelongsTo<CanteenStand, $this> */
    public function stand(): BelongsTo
    {
        return $this->belongsTo(CanteenStand::class, 'canteen_stand_id');
    }

    /** @return HasMany<CanteenOrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(CanteenOrderItem::class);
    }
}
