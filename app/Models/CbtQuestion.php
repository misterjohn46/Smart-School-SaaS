<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtQuestion extends Model
{
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'score_weight' => 'integer',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(CbtBank::class, 'cbt_bank_id');
    }
}
