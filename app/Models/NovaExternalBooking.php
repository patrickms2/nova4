<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class NovaExternalBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'nova_business_id',
        'nova_service_id',
        'nova_external_customer_id',
        'nova_external_catalog_item_id',
        'source',
        'external_id',
        'external_item_id',
        'intent_key',
        'service_name',
        'booking_date',
        'booking_time',
        'booking_starts_at',
        'booking_ends_at',
        'attendees',
        'adults',
        'children',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total',
        'currency',
        'booking_status',
        'payment_status',
        'confirmation_code',
        'admin_url',
        'metadata',
        'source_updated_at',
        'source_fingerprint',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'booking_starts_at' => 'datetime',
            'booking_ends_at' => 'datetime',
            'total' => 'decimal:2',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(NovaExternalCustomer::class, 'nova_external_customer_id');
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(NovaExternalCatalogItem::class, 'nova_external_catalog_item_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(NovaExternalTransaction::class);
    }

    public function links(): MorphMany
    {
        return $this->morphMany(NovaIntegrationLink::class, 'linkable');
    }
}
