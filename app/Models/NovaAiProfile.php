<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NovaAiProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'nova_business_id',
        'nova_service_id',
        'name',
        'provider',
        'model',
        'status',
        'system_prompt',
        'temperature',
        'max_tokens',
        'tools_policy',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'decimal:2',
            'max_tokens' => 'integer',
            'tools_policy' => 'array',
            'settings' => 'array',
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

    public function knowledge(): HasMany
    {
        return $this->hasMany(NovaAiKnowledge::class);
    }
}
