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
        Schema::create('student_report_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete(); // Wali Kelas
            $table->unsignedInteger('attendance_sick')->default(0);
            $table->unsignedInteger('attendance_permission')->default(0);
            $table->unsignedInteger('attendance_unexcused')->default(0);
            $table->text('homeroom_notes')->nullable();
            $table->json('extracurriculars')->nullable(); // [{"name": "Pramuka", "grade": "A", "description": "..."}]
            $table->enum('promotion_status', ['naik_kelas', 'tinggal_kelas', 'lulus', 'belum_lulus', 'dalam_proses'])->default('dalam_proses');
            $table->timestamps();

            $table->unique(['academic_year_id', 'student_profile_id'], 'unique_academic_student_report');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_report_remarks');
    }
};
