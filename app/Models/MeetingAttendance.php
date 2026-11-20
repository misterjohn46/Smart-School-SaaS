<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendance extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke Rapat Terkait
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(SchoolMeeting::class, 'school_meeting_id');
    }

    /**
     * Relasi ke Akun User Guru / Staf (jika terdaftar)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
