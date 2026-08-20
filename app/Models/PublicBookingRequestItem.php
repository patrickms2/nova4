<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicBookingRequestItem extends Model
{
    protected $fillable = [
        'public_booking_request_id',
        'item_type',
        'service_id',
        'service_name',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_amount',
        'total',
        'currency',
        'starts_at',
        'metadata',
        'remote_booking_status',
        'remote_source_platform',
        'remote_external_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'starts_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(PublicBookingRequest::class, 'public_booking_request_id');
    }
}
