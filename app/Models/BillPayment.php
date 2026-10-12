<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPayment extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount_allocated' => 'decimal:2',
    ];

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<StudentBill, $this> */
    public function studentBill(): BelongsTo
    {
        return $this->belongsTo(StudentBill::class);
    }
}
