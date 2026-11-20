<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpdbRegistration extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'documents' => 'array',
            'verified_at' => 'datetime',
            'average_report_score' => 'float',
            'distance_meters' => 'integer',
        ];
    }

    /**
     * Relasi ke Gelombang Pendaftaran
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(PpdbBatch::class, 'ppdb_batch_id');
    }

    /**
     * Relasi ke Pilihan Jurusan Pertama
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Relasi ke Pilihan Jurusan Kedua
     */
    public function secondDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'second_department_id');
    }

    /**
     * Petugas / Panitia yang memverifikasi berkas
     */
    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Profil Siswa Resmi jika sudah dikonversi
     */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }
}
