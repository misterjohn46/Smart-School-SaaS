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
        Schema::create('teaching_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lesson_schedule_id')->nullable()->constrained('lesson_schedules')->nullOnDelete();

            $table->date('date');
            $table->string('time_slot', 50)->nullable(); // Contoh: Jam 1 - 3 (07:15 - 09:30)
            $table->string('topic_title', 255);          // Materi Pokok / TP / CP
            $table->string('teaching_method', 50)->default('teori_praktik'); // teori, praktik_lab, diskusi, kuis_asesmen
            $table->text('learning_activity');           // Uraian Kegiatan Pembelajaran
            $table->text('class_notes')->nullable();     // Catatan Khusus Kejadian Kelas

            // Ringkasan Presensi Siswa pada Pertemuan Ini
            $table->unsignedSmallInteger('attendance_present')->default(0);
            $table->unsignedSmallInteger('attendance_sick')->default(0);
            $table->unsignedSmallInteger('attendance_permission')->default(0);
            $table->unsignedSmallInteger('attendance_absent')->default(0);

            // Status Keterlaksanaan Pembelajaran
            $table->enum('status', ['terlaksana', 'tugas_mandiri', 'pengganti', 'daring'])->default('terlaksana');
            $table->string('substitute_teacher_name', 100)->nullable();
            $table->string('documentation_photo', 255)->nullable();

            // Supervisi Kepala Sekolah / Waka Kurikulum
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['teacher_user_id', 'date']);
            $table->index(['classroom_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_journals');
    }
};
