<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NovaIntegrationSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'nova_integration_setting_id',
        'nova_business_id',
        'nova_service_id',
        'source',
        'job_name',
        'entity_type',
        'payload_hash',
        'status',
        'processed_count',
        'created_count',
        'updated_count',
        'error_message',
        'metadata',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function integrationSetting(): BelongsTo
    {
        return $this->belongsTo(NovaIntegrationSetting::class, 'nova_integration_setting_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(NovaService::class, 'nova_service_id');
    }
}
