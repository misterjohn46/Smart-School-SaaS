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
        Schema::create('lesson_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('lesson_schedule_id')->nullable()->constrained('lesson_schedules')->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->string('notes')->nullable();
            $table->timestamps();

            // Indeks untuk kecepatan kueri dan keunikan absensi siswa per jam pelajaran pada tanggal tertentu
            $table->unique(['lesson_schedule_id', 'student_user_id', 'date'], 'lesson_att_sched_stud_date_uniq');
            $table->index(['classroom_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_attendances');
    }
};
