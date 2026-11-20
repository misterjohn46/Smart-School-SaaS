<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Papan Pengumuman Digital Sekolah
        Schema::create('school_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index('school_announcements_tenant_id_index');
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('content');
            $table->enum('category', ['akademik', 'keuangan', 'kegiatan', 'penting'])->default('akademik');
            $table->enum('target_audience', ['semua', 'siswa', 'guru', 'orang_tua'])->default('semua');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // 2. Materi Pembelajaran & Tugas Harian Kelas (LMS / E-Learning)
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index('learning_materials_tenant_id_index');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['material', 'assignment'])->default('material');
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('reference_url', 255)->nullable();
            $table->dateTime('due_date')->nullable();
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['classroom_id', 'subject_id', 'type']);
        });

        // 3. Pengumpulan & Penilaian Tugas Siswa
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index('assignment_submissions_tenant_id_index');
            $table->foreignId('learning_material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->string('attachment_url', 255)->nullable();
            $table->dateTime('submitted_at');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['learning_material_id', 'student_profile_id'], 'submission_material_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('school_announcements');
    }
};
