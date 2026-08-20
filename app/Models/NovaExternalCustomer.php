<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class NovaExternalCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'nova_business_id',
        'nova_service_id',
        'source',
        'external_id',
        'name',
        'email',
        'phone',
        'metadata',
        'source_updated_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
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

    public function bookings(): HasMany
    {
        return $this->hasMany(NovaExternalBooking::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(NovaExternalOrder::class);
    }

    public function links(): MorphMany
    {
        return $this->morphMany(NovaIntegrationLink::class, 'linkable');
    }
}
