<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingRide extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'ride_id',
        'offer_id',
        'ride_recommendation_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'party_size',
        'booking_for',
        'status',
        'amount',
        'commission_amount',
        'notes',
    ];

    protected $casts = [
        'booking_for' => 'datetime',
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $booking): void {
            if (blank($booking->uuid)) {
                $booking->uuid = (string)Str::uuid();
            }
        });
    }

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(RideRecommendation::class, 'ride_recommendation_id');
    }
}
