<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationEvent extends Model
{
    //
    protected $fillable = [
        'ride_id',
        'offer_id',
        'position',
        'shown_at',
        'clicked_at',
        'converted_at',
    ];

    protected $casts = [
        'shown_at' => 'datetime',
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
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
