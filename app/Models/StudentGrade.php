<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGrade extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'assignment_score' => 'float',
        'quiz_score' => 'float',
        'midterm_score' => 'float',
        'final_score' => 'float',
        'overall_score' => 'float',
        'is_passed' => 'boolean',
    ];

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

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    /**
     * Hitung nilai akhir, predikat huruf, dan status ketuntasan KKM
     * Bobot: 20% Tugas + 20% Kuis/UH + 30% UTS + 30% UAS
     */
    public static function calculateScore(
        float $assignment,
        float $quiz,
        float $midterm,
        float $final,
        int $kkm = 75
    ): array {
        $overall = ($assignment * 0.20) + ($quiz * 0.20) + ($midterm * 0.30) + ($final * 0.30);
        $overall = round($overall, 2);

        $letter = match (true) {
            $overall >= 90 => 'A',
            $overall >= 80 => 'B',
            $overall >= $kkm => 'C',
            default => 'D',
        };

        $isPassed = $overall >= $kkm;

        return [
            'overall_score' => $overall,
            'letter_grade' => $letter,
            'is_passed' => $isPassed,
        ];
    }
}
