<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalSettlement extends Model
{
    /** @use HasFactory<\Database\Factories\RentalSettlementFactory> */
    use HasFactory;

    protected $fillable = [
        'rental_reservation_id',
        'status',
        'accommodation_amount',
        'channel_commission_amount',
        'commissionable_base',
        'manager_commission_amount',
        'services_amount',
        'estimated_net',
        'real_payout',
        'difference',
        'confirmed_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'accommodation_amount' => 'decimal:2',
        'channel_commission_amount' => 'decimal:2',
        'commissionable_base' => 'decimal:2',
        'manager_commission_amount' => 'decimal:2',
        'services_amount' => 'decimal:2',
        'estimated_net' => 'decimal:2',
        'real_payout' => 'decimal:2',
        'difference' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RentalReservation::class, 'rental_reservation_id');
    }
}
