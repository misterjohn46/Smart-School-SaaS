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
        // 1. Perangkat Keras IoT Presensi (ESP32 RFID Reader)
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_token', 64)->unique();
            $table->string('device_name', 100);
            $table->string('location', 100);
            $table->enum('device_type', ['gate_in', 'gate_out', 'two_way', 'canteen'])->default('two_way');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Pengaturan Geofencing Presensi GPS Sekolah
        Schema::create('school_geofences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->default('Kampus Utama SMK');
            $table->decimal('latitude', 10, 7)->default(-6.2088000);
            $table->decimal('longitude', 10, 7)->default(106.8456000);
            $table->unsignedInteger('radius_meters')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Catatan Kehadiran Harian Siswa & Guru
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->date('date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->enum('method', ['rfid', 'qrcode', 'face_recognition', 'gps_mobile', 'manual'])->default('rfid');
            $table->foreignId('device_id')->nullable()->constrained('attendance_devices')->nullOnDelete();
            $table->string('device_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        // 4. Pengajuan Izin / Sakit (E-Leave)
        Schema::create('attendance_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['sakit', 'izin', 'tugas_luar'])->default('izin');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // 5. Template Kartu Tanda Siswa (KTS) Digital ISO CR-80
        Schema::create('kts_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name', 100)->default('Standar ISO CR-80');
            $table->string('primary_color', 20)->default('#4f46e5');
            $table->string('secondary_color', 20)->default('#f59e0b');
            $table->string('background_pattern', 50)->default('geometric');
            $table->boolean('show_rfid_chip')->default(true);
            $table->boolean('show_barcode')->default(true);
            $table->boolean('show_school_logo')->default(true);
            $table->string('signatory_name')->default('Dr. H. Bambang Sudarmono, M.Pd');
            $table->string('signatory_title')->default('Kepala Sekolah');
            $table->string('signatory_signature_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kts_templates');
        Schema::dropIfExists('attendance_leaves');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('school_geofences');
        Schema::dropIfExists('attendance_devices');
    }
};
