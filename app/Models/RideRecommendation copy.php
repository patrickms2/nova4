<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'ride_id',
        'offer_id',
        'position',
        'score_total',
        'score_proximity',
        'score_availability',
        'score_popularity',
        'score_priority',
        'score_conversion',
        'score_context',
        'score_authenticity',
        'primary_reason',
        'reason_payload',
        'was_viewed',
        'was_clicked',
        'clicked_at',
        'was_booked',
        'booked_at',
    ];

    protected $casts = [
        'reason_payload' => 'array',
        'was_viewed' => 'boolean',
        'was_clicked' => 'boolean',
        'was_booked' => 'boolean',
        'clicked_at' => 'datetime',
        'booked_at' => 'datetime',
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
