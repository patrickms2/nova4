<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NovaService extends Model
{
    protected $fillable = [
        'nova_business_id',
        'name',
        'code',
        'service_type',
        'status',
        'has_development',
        'has_maintenance',
        'has_whatsapp',
        'has_mcp',
        'has_sales',
        'has_services',
        'monthly_amount',
        'commission_rate',
        'settings',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (NovaService $service): void {
            if (blank($service->getKey())) {
                $service->setAttribute($service->getKeyName(), ((int) static::query()->max($service->getKeyName())) + 1);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'has_development' => 'boolean',
            'has_maintenance' => 'boolean',
            'has_whatsapp' => 'boolean',
            'has_mcp' => 'boolean',
            'has_sales' => 'boolean',
            'has_services' => 'boolean',
            'monthly_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'settings' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }

    public function aiProfiles(): HasMany
    {
        return $this->hasMany(NovaAiProfile::class, 'nova_service_id');
    }
}
