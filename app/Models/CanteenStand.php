<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenStand extends Model
{
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'total_revenue' => 'decimal:2',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CanteenMenu, $this> */
    public function menus(): HasMany
    {
        return $this->hasMany(CanteenMenu::class);
    }

    /** @return HasMany<CanteenOrder, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(CanteenOrder::class);
    }
}
