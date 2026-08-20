<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'external_source_id',
        'business_name',
        'source_platform',
        'source_label',
        'external_id',
        'external_item_id',
        'intent_key',
        'booking_type',
        'resource_type',
        'target_model',
        'status',
        'payment_status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'service_name',
        'starts_at',
        'ends_at',
        'party_size',
        'quantity',
        'total',
        'currency',
        'admin_url',
        'metadata',
        'source_updated_at',
        'source_fingerprint',
        'last_synced_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'party_size' => 'integer',
        'quantity' => 'integer',
        'total' => 'decimal:2',
        'metadata' => 'array',
        'source_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function externalSource(): BelongsTo
    {
        return $this->belongsTo(ExternalSource::class);
    }
}
