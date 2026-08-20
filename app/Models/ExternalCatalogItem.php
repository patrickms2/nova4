<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalCatalogItem extends Model
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
        'type',
        'status',
        'name',
        'description',
        'short_description',
        'sku',
        'price',
        'regular_price',
        'sale_price',
        'currency',
        'booking_url',
        'purchase_url',
        'admin_url',
        'metadata',
        'source_updated_at',
        'source_fingerprint',
        'last_synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
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
