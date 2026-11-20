<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpdbBatch extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'announcement_date' => 'date',
            'registration_fee' => 'decimal:2',
            'quota' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke Tahun Ajaran Akademik
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relasi ke Seluruh Pendaftar pada Gelombang ini
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(PpdbRegistration::class);
    }

    /**
     * Cek apakah pendaftaran gelombang sedang buka saat ini
     */
    public function isOpen(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = Carbon::today();
        return $now->gte($this->start_date) && $now->lte($this->end_date);
    }
}
