<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'event_type',
        'ride_id',
        'offer_id',
        'booking_id',
        'ride_recommendation_id',
        'session_id',
        'meta',
        'value_amount',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'value_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingRide::class);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(RideRecommendation::class, 'ride_recommendation_id');
    }
}
