<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CbtNode extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'max_concurrent_students' => 'integer',
        'current_active_students' => 'integer',
        'last_heartbeat_at' => 'datetime',
    ];
}
