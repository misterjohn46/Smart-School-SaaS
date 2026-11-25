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
        // 1. Tabel Rapat Sekolah, Notulen & BAP
        Schema::create('school_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('title', 200);
            $table->enum('meeting_type', [
                'dewan_guru',
                'pleno',
                'wali_murid',
                'komite',
                'koordinasi_jurusan',
                'kedisiplinan',
                'lainnya',
            ])->default('dewan_guru');
            $table->string('meeting_number', 60)->nullable(); // Nomor BAP: BAP-RAPAT/2026/X/001
            $table->date('meeting_date');
            $table->string('start_time', 10); // 08:30
            $table->string('end_time', 10)->nullable(); // 11:00
            $table->string('location', 150); // Ruang Guru & Meeting Hall
            $table->string('meeting_link', 255)->nullable(); // Zoom / Google Meet

            // Pimpinan & Notulis Rapat
            $table->foreignId('leader_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('leader_name', 150)->nullable();
            $table->foreignId('secretary_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('secretary_name', 150)->nullable();

            $table->text('agenda')->nullable();
            $table->string('target_audience', 150)->nullable(); // Dewan Guru, Staf TU, dll
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');

            // Notulen, Resume Keputusan & Tindak Lanjut (Action Items)
            $table->longText('notes')->nullable();
            $table->text('decisions')->nullable();
            $table->json('action_items')->nullable(); // [{"task": "...", "pic": "...", "due_date": "...", "is_done": false}]

            // Dokumentasi & Berkas Lampiran
            $table->json('attachments')->nullable(); // [{"title": "...", "path": "...", "size": "..."}]
            $table->json('photos')->nullable(); // ["path/to/foto1.jpg", ...]

            // Tanda Tangan Pimpinan & Pengesahan BAP
            $table->longText('leader_signature')->nullable(); // Data URL PNG
            $table->timestamp('bap_generated_at')->nullable();

            $table->timestamps();
        });

        // 2. Presensi Peserta Rapat & Tanda Tangan Digital
        Schema::create('meeting_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('school_meeting_id')->constrained('school_meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('participant_name', 150);
            $table->string('participant_role', 100)->nullable(); // Guru Mapel / Wali Murid / Komite
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->string('notes', 255)->nullable(); // Keterangan izin/sakit
            $table->longText('signature')->nullable(); // Canvas Data URL Base64 PNG
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['school_meeting_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendances');
        Schema::dropIfExists('school_meetings');
    }
};
