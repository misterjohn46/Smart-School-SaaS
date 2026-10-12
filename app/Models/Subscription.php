<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Subscription extends Model
{
    // Tabel ini hanya ada di database central (platform SaaS), bukan di database tenant.
    use CentralConnection;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isValid(): bool
    {
        if ($this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture()) {
            return true;
        }

        return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
