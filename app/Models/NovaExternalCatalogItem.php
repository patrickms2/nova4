<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class NovaExternalCatalogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'nova_business_id',
        'nova_service_id',
        'nova_integration_setting_id',
        'source',
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
        'currency',
        'stock_status',
        'image_url',
        'purchase_url',
        'booking_url',
        'location_label',
        'duration_minutes',
        'capacity',
        'metadata',
        'source_updated_at',
        'source_fingerprint',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'regular_price' => 'decimal:2',
            'metadata' => 'array',
            'source_updated_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(NovaService::class, 'nova_service_id');
    }

    public function integrationSetting(): BelongsTo
    {
        return $this->belongsTo(NovaIntegrationSetting::class, 'nova_integration_setting_id');
    }

    public function links(): MorphMany
    {
        return $this->morphMany(NovaIntegrationLink::class, 'linkable');
    }
}
