<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class NovaIntegrationLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'linkable_type',
        'linkable_id',
        'nova_integration_setting_id',
        'source',
        'external_id',
        'external_item_id',
        'intent_key',
        'url',
        'metadata',
        'source_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'source_updated_at' => 'datetime',
        ];
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function integrationSetting(): BelongsTo
    {
        return $this->belongsTo(NovaIntegrationSetting::class, 'nova_integration_setting_id');
    }
}
