<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('assignment_score', 5, 2)->nullable()->default(0); // Nilai Tugas / PR
            $table->decimal('quiz_score', 5, 2)->nullable()->default(0);       // Nilai Kuis / Ulangan Harian (Formatif)
            $table->decimal('midterm_score', 5, 2)->nullable()->default(0);    // Nilai UTS
            $table->decimal('final_score', 5, 2)->nullable()->default(0);      // Nilai UAS (Sumatif)

            $table->decimal('overall_score', 5, 2)->nullable()->default(0);    // Nilai Akhir Hasil Pembobotan
            $table->string('letter_grade', 5)->nullable()->default('-');       // Predikat (A, B, C, D)
            $table->boolean('is_passed')->default(true);                       // Status Ketuntasan KKM
            $table->text('notes')->nullable();                                 // Catatan Akademik / Saran Guru

            $table->timestamps();

            $table->unique(
                ['academic_year_id', 'classroom_id', 'subject_id', 'student_profile_id'],
                'unique_student_grade_record'
            );
            $table->index(['classroom_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
