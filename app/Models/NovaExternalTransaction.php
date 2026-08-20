<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NovaExternalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'nova_business_id',
        'nova_service_id',
        'nova_external_booking_id',
        'nova_external_order_id',
        'source',
        'gateway',
        'gateway_ref',
        'amount',
        'currency',
        'status',
        'method',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(NovaExternalBooking::class, 'nova_external_booking_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(NovaExternalOrder::class, 'nova_external_order_id');
    }
}
