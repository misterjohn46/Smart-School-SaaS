<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    // Tabel ini hanya ada di database central (platform SaaS), bukan di database tenant.
    use CentralConnection;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
