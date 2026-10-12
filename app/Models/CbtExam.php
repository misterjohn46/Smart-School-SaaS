<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtExam extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'duration_minutes' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
        'show_results' => 'boolean',
    ];

    /** @return BelongsTo<CbtBank, $this> */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(CbtBank::class, 'cbt_bank_id');
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return HasMany<CbtSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(CbtSubmission::class);
    }

    /**
     * Generate token acak 6 karakter unik (huruf kapital & angka tanpa karakter ambigu)
     */
    public static function generateToken(): string
    {
        $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $token = '';
        for ($i = 0; $i < 6; $i++) {
            $token .= $pool[random_int(0, strlen($pool) - 1)];
        }

        return $token;
    }
}
