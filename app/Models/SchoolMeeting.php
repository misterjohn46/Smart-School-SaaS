<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolMeeting extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'action_items' => 'array',
            'attachments' => 'array',
            'photos' => 'array',
            'bap_generated_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke Pimpinan Rapat (Kepala Sekolah / Guru Senior)
     */
    public function leaderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    /**
     * Relasi ke Notulis / Sekretaris Rapat
     */
    public function secretaryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary_user_id');
    }

    /**
     * Relasi ke Seluruh Daftar Hadir Presensi Peserta Rapat
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    /**
     * Helper Label Tipe Rapat
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->meeting_type) {
            'dewan_guru' => 'Rapat Dewan Guru',
            'pleno' => 'Rapat Pleno Sekolah',
            'wali_murid' => 'Rapat Pertemuan Orang Tua / Wali',
            'komite' => 'Rapat Komite Sekolah',
            'koordinasi_jurusan' => 'Rapat Koordinasi Jurusan / KKG',
            'kedisiplinan' => 'Rapat Evaluasi & Kedisiplinan',
            default => 'Rapat Umum / Lainnya',
        };
    }
}
