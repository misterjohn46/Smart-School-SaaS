<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KtsTemplate extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'show_rfid_chip' => 'boolean',
        'show_barcode' => 'boolean',
        'show_school_logo' => 'boolean',
        'is_active' => 'boolean',
    ];
}
