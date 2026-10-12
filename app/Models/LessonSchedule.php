<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonSchedule extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // ─── Relasi Data ──────────────────────────────────────────────

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

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

    /** @return HasMany<LessonAttendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(LessonAttendance::class, 'lesson_schedule_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeToday(Builder $query): Builder
    {
        $dayMap = [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
        ];

        $today = $dayMap[now()->dayOfWeek] ?? null;

        if (! $today) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('day_of_week', $today);
    }

    public function scopeForDay(Builder $query, string $day): Builder
    {
        return $query->where('day_of_week', strtolower($day));
    }

    // ─── Accessors ────────────────────────────────────────────────
    public function getTimeSlotAttribute(): string
    {
        $start = substr((string) $this->start_time, 0, 5);
        $end = substr((string) $this->end_time, 0, 5);

        return "{$start} - {$end}";
    }

    public function getDayLabelAttribute(): string
    {
        return match ($this->day_of_week) {
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            'sabtu' => 'Sabtu',
            default => ucfirst((string) $this->day_of_week),
        };
    }
}
