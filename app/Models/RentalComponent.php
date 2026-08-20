<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalComponent extends Model
{
    /** @use HasFactory<\Database\Factories\RentalComponentFactory> */
    use HasFactory;

    protected $fillable = [
        'rental_reservation_id',
        'type',
        'label',
        'amount',
        'generates_commission',
        'is_income',
        'is_expense',
        'provider_name',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'generates_commission' => 'boolean',
        'is_income' => 'boolean',
        'is_expense' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RentalReservation::class, 'rental_reservation_id');
    }
}
