<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class StudentWallet extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $hidden = [
        'pin',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'daily_limit' => 'decimal:2',
        'spent_today' => 'decimal:2',
        'last_spent_date' => 'date',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return HasMany<WalletTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function verifyPin(string $pin): bool
    {
        return Hash::check($pin, $this->pin);
    }

    public function setPin(string $pin): void
    {
        $this->update(['pin' => Hash::make($pin)]);
    }
}
