<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalPayment extends Model
{
    /** @use HasFactory<\Database\Factories\RentalPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'rental_reservation_id',
        'source',
        'amount',
        'paid_at',
        'expected_at',
        'status',
        'transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
        'expected_at' => 'date',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RentalReservation::class, 'rental_reservation_id');
    }
}
