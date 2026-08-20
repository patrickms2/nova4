<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    public $timestamps = false;

    protected $table = 'taxis_booking';

    protected $primaryKey = 'bookingid';

    protected $guarded = [];

    protected $casts = [
        'bookingDate' => 'datetime',
        'pickupAddress_latitude' => 'float',
        'pickupAddress_longitude' => 'float',
        'destinationAddress_latitude' => 'float',
        'destinationAddress_longitude' => 'float',
        'booking_latitude' => 'float',
        'booking_longitude' => 'float',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }
}
