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
        // 1. Gelombang Pendaftaran PPDB
        Schema::create('ppdb_batches', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('name', 150); // Contoh: Gelombang 1 - Jalur Prestasi & Zonasi
            $table->date('start_date');
            $table->date('end_date');
            $table->date('announcement_date')->nullable();
            $table->decimal('registration_fee', 12, 2)->default(0);
            $table->unsignedInteger('quota')->default(100);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Data Calon Siswa Baru / Pendaftar PPDB
        Schema::create('ppdb_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('ppdb_batch_id')->constrained('ppdb_batches')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('second_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('registration_number', 35);
            $table->string('access_token', 64)->nullable(); // PIN / Secret untuk cek status
            $table->enum('track', ['zonasi', 'prestasi', 'afirmasi', 'reguler', 'pindahan'])->default('reguler');

            // Data Pribadi Calon Siswa
            $table->string('full_name', 150);
            $table->string('nisn', 20)->nullable();
            $table->string('nik', 20)->nullable();
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('birth_place', 60)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 30)->nullable();
            $table->string('previous_school', 150)->nullable(); // Asal Sekolah
            $table->string('phone_number', 25)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('distance_meters')->nullable(); // Untuk jalur zonasi
            $table->decimal('average_report_score', 5, 2)->nullable(); // Nilai rata-rata rapor
            $table->text('achievements')->nullable(); // Piagam & prestasi

            // Data Orang Tua / Wali
            $table->string('parent_name', 150)->nullable();
            $table->string('parent_phone', 25)->nullable();
            $table->string('parent_job', 100)->nullable();
            $table->string('parent_income', 50)->nullable();

            // Berkas Uploaded (JSON): { kk, akta, ijazah, foto }
            $table->json('documents')->nullable();

            // Status Seleksi & Pembayaran
            $table->enum('payment_status', ['unpaid', 'paid', 'waived'])->default('waived');
            $table->enum('status', ['pending', 'verified', 'rejected', 'accepted', 'not_accepted', 'enrolled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            // Tautan Akun Siswa Resmi (setelah konversi)
            $table->foreignId('student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'registration_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppdb_registrations');
        Schema::dropIfExists('ppdb_batches');
    }
};
