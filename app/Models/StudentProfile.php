<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentProfile extends Model
{
    use BelongsToSchool;
    use HasFactory, HasObfuscatedId;

    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
        'face_descriptor' => 'array',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return BelongsTo<User, $this> */
    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /** @return HasOne<StudentWallet, $this> */
    public function wallet(): HasOne
    {
        return $this->hasOne(StudentWallet::class);
    }

    /** @return HasMany<StudentBill, $this> */
    public function bills(): HasMany
    {
        return $this->hasMany(StudentBill::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<CanteenOrder, $this> */
    public function canteenOrders(): HasMany
    {
        return $this->hasMany(CanteenOrder::class);
    }

    /** @return HasMany<StudentViolation, $this> */
    public function studentViolations(): HasMany
    {
        return $this->hasMany(StudentViolation::class, 'student_id');
    }
}
