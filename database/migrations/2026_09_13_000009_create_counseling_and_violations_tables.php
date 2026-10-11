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
        // 1. Tabel Kategori & Aturan Tata Tertib / Prestasi Sekolah
        Schema::create('violation_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->enum('type', ['pelanggaran', 'prestasi'])->default('pelanggaran');
            $table->enum('severity', ['ringan', 'sedang', 'berat', 'sangat_berat', 'prestasi'])->default('ringan');
            $table->unsignedInteger('points')->default(5);
            $table->text('action_recommendation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Rekam Catatan Kedisiplinan & Pelanggaran Siswa
        Schema::create('student_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('violation_category_id')->constrained('violation_categories')->cascadeOnDelete();
            $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->text('notes');
            $table->unsignedInteger('points')->default(5);
            $table->text('sanction')->nullable();
            $table->text('follow_up')->nullable();
            $table->enum('status', ['tercatat', 'dalam_pembinaan', 'selesai', 'panggilan_orang_tua'])->default('tercatat');
            $table->boolean('parent_notified')->default(false);
            $table->timestamp('parent_notified_at')->nullable();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'date']);
            $table->index(['academic_year_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_violations');
        Schema::dropIfExists('violation_categories');
    }
};
