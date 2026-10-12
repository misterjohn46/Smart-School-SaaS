<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'kkm' => 'integer',
        'is_active' => 'boolean',
    ];
}
