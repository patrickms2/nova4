<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\NovaAiKnowledgeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([NovaAiKnowledgeObserver::class])]
final class NovaAiKnowledge extends Model
{
    use HasFactory;

    protected $table = 'nova_ai_knowledge';

    protected $fillable = [
        'nova_business_id',
        'nova_service_id',
        'nova_ai_profile_id',
        'title',
        'content',
        'status',
        'metadata',
        'embedding',
        'vectorized_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'embedding' => 'array',
            'vectorized_at' => 'datetime',
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

    public function aiProfile(): BelongsTo
    {
        return $this->belongsTo(NovaAiProfile::class, 'nova_ai_profile_id');
    }
}
