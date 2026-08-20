<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'external_source_id',
        'business_name',
        'source_platform',
        'source_label',
        'external_id',
        'external_increment_id',
        'status',
        'payment_status',
        'customer_name',
        'customer_email',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'grand_total',
        'currency',
        'payment_method',
        'shipping_method',
        'ordered_at',
        'items',
        'admin_url',
        'metadata',
        'source_updated_at',
        'source_fingerprint',
        'last_synced_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'ordered_at' => 'datetime',
        'items' => 'array',
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
