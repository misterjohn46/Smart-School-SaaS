<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /** @return BelongsToMany<StudentBill, $this> */
    public function bills(): BelongsToMany
    {
        return $this->belongsToMany(StudentBill::class, 'bill_payments')
            ->withPivot('amount_allocated')
            ->withTimestamps();
    }

    /** @return HasMany<BillPayment, $this> */
    public function billPayments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }
}
