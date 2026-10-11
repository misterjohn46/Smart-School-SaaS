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
        // 1. Bank Soal CBT
        Schema::create('cbt_banks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('total_questions')->default(0);
            $table->float('passing_score')->default(75.0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Butir Soal CBT
        Schema::create('cbt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_bank_id')->constrained('cbt_banks')->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('question_type', ['multiple_choice', 'essay', 'true_false'])->default('multiple_choice');
            $table->json('options')->nullable(); // [{"key": "A", "text": "..."}, ...]
            $table->string('correct_answer', 50)->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedInteger('score_weight')->default(10);
            $table->timestamps();
        });

        // 3. Sesi / Jadwal Ujian CBT
        Schema::create('cbt_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_bank_id')->constrained('cbt_banks')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('title', 150);
            $table->string('token', 10);
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->boolean('randomize_questions')->default(true);
            $table->boolean('randomize_options')->default(false);
            $table->boolean('show_results')->default(true);
            $table->enum('status', ['draft', 'published', 'ongoing', 'completed'])->default('published');
            $table->timestamps();
        });

        // 4. Lembar Jawaban & Nilai Siswa CBT
        Schema::create('cbt_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_exam_id')->constrained('cbt_exams')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->json('answers')->nullable(); // {"question_id": "A"}
            $table->float('total_score')->default(0);
            $table->boolean('is_passed')->default(false);
            $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('submitted');
            $table->timestamps();

            $table->unique(['cbt_exam_id', 'student_profile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cbt_submissions');
        Schema::dropIfExists('cbt_exams');
        Schema::dropIfExists('cbt_questions');
        Schema::dropIfExists('cbt_banks');
    }
};
