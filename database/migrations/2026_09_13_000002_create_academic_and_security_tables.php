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
        // 1. Perbarui tabel users dengan kolom multi-metode login & security
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('nisn', 20)->unique()->nullable()->after('username');
            $table->string('phone_number', 25)->nullable()->after('email');
            $table->string('pin_hash')->nullable()->after('password'); // PIN 6-digit e-wallet & transaksi
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->boolean('is_locked')->default(false)->after('is_active');
            $table->timestamp('lockout_until')->nullable()->after('is_locked');
            $table->string('lockout_reason')->nullable()->after('lockout_until');
        });

        // 2. Tabel Audit Trail & Log Keamanan Login
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->default('login_attempt');
            $table->string('identifier_attempted')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('status', 30); // success, failed, locked, otp_sent, otp_verified
            $table->string('failure_reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 3. Tahun Akademik & Semester
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Contoh: 2024/2025
            $table->enum('semester', ['ganjil', 'genap'])->default('ganjil');
            $table->boolean('is_active')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        // 4. Program Keahlian / Jurusan (SD, SMP, SMA, SMK)
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // RPL, TKJ, IPA, UMUM
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Tingkat Kelas (Tingkat 10, 11, 12 / 7, 8, 9 / 1 s/d 6)
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30); // Tingkat X, Tingkat XI, dll
            $table->unsignedSmallInteger('level_numeric'); // 10, 11, 12
            $table->timestamps();
        });

        // 6. Rombongan Belajar (Kelas / Classrooms)
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();
            $table->string('name', 50); // Contoh: X - RPL 1
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('capacity')->default(36);
            $table->timestamps();
        });

        // 7. Mata Pelajaran (Subjects)
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->unsignedSmallInteger('kkm')->default(75);
            $table->string('type', 30)->default('umum'); // umum, kejuruan, mulok
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 8. Profil Guru & Tenaga Kependidikan
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nip', 30)->nullable();
            $table->string('nuptk', 30)->nullable();
            $table->string('full_name');
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('phone_wa', 25)->nullable();
            $table->text('address')->nullable();
            $table->string('rfid_uid', 50)->unique()->nullable();
            $table->string('photo_path')->nullable();
            $table->json('face_descriptor')->nullable(); // Vektor 128-float numerik AI wajah
            $table->timestamps();
        });

        // 9. Profil Siswa & Relasi Orang Tua
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('nisn', 20)->unique();
            $table->string('nik', 20)->nullable();
            $table->string('full_name');
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('birth_place', 60)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('rfid_uid', 50)->unique()->nullable();
            $table->string('photo_path')->nullable();
            $table->json('face_descriptor')->nullable(); // Vektor 128-float numerik AI wajah
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone_wa', 25)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('teacher_profiles');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('audit_trails');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'nisn',
                'phone_number',
                'pin_hash',
                'is_active',
                'is_locked',
                'lockout_until',
                'lockout_reason',
            ]);
        });
    }
};
