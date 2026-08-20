<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovaIntentRule extends Model
{
    protected $fillable = [
        'nova_business_id',
        'intent_key',
        'rule_type',
        'keywords',
        'description',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('priority');
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('nova_business_id');
    }

    public function scopeForIntent(Builder $query, string $intent): Builder
    {
        return $query->where('intent_key', $intent);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('rule_type', $type);
    }
}
