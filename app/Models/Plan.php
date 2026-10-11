<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    // Tabel ini hanya ada di database central (platform SaaS), bukan di database tenant.
    use CentralConnection;

    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'price_monthly' => 'integer',
        'price_yearly' => 'integer',
        'max_students' => 'integer',
        'max_teachers' => 'integer',
        'max_storage_mb' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
