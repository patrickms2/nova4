<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class NovaExternalOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'nova_business_id',
        'nova_service_id',
        'nova_external_customer_id',
        'source',
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
        'admin_url',
        'items',
        'metadata',
        'source_updated_at',
        'source_fingerprint',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
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

    public function transactions(): HasMany
    {
        return $this->hasMany(NovaExternalTransaction::class);
    }

    public function links(): MorphMany
    {
        return $this->morphMany(NovaIntegrationLink::class, 'linkable');
    }
}
