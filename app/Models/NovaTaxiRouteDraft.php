<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NovaTaxiRouteDraft extends Model
{
    protected $fillable = [
        'token',
        'tourist_phone',
        'customer_name',
        'customer_phone',
        'origin',
        'destination',
        'pickup_date',
        'pickup_time',
        'passengers',
        'status',
        'chbs_url',
        'external_booking_id',
        'woo_order_id',
        'conversation',
        'paid_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'conversation' => 'array',
            'pickup_date' => 'date',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
