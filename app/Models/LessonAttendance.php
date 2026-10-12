<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAttendance extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return BelongsTo<LessonSchedule, $this> */
    public function lessonSchedule(): BelongsTo
    {
        return $this->belongsTo(LessonSchedule::class);
    }

    /** @return BelongsTo<User, $this> */
    public function studentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function scopeForClassroom(Builder $query, int $classroomId): Builder
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopeForSchedule(Builder $query, ?int $scheduleId): Builder
    {
        if ($scheduleId) {
            return $query->where('lesson_schedule_id', $scheduleId);
        }

        return $query->whereNull('lesson_schedule_id');
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }
}
