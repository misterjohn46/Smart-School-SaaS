<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class CbtNode extends Model
{
    // Tabel ini hanya ada di database central (platform SaaS), bukan di database tenant.
    use CentralConnection;

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'max_concurrent_students' => 'integer',
        'current_active_students' => 'integer',
        'last_heartbeat_at' => 'datetime',
    ];
}
