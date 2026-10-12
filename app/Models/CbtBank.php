<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtBank extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'passing_score' => 'float',
        'total_questions' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    /** @return HasMany<CbtQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(CbtQuestion::class);
    }

    /** @return HasMany<CbtExam, $this> */
    public function exams(): HasMany
    {
        return $this->hasMany(CbtExam::class);
    }
}
