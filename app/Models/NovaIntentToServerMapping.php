<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovaIntentToServerMapping extends Model
{
    protected $table = 'nova_intent_to_server_mapping';

    protected $fillable = [
        'nova_business_id',
        'intent_key',
        'server_id',
        'tool_id',
        'priority',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'tool_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForIntent($query, string $intentKey)
    {
        return $query->where('intent_key', $intentKey);
    }

    public function scopeForBusiness($query, ?int $businessId)
    {
        return $query->where(function ($q) use ($businessId) {
            $q->whereNull('nova_business_id')
              ->orWhere('nova_business_id', $businessId);
        });
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
