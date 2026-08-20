<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovaCrossSellingRule extends Model
{
    protected $fillable = [
        'from_business_id',
        'to_business_id',
        'trigger_intent',
        'message',
        'cta_label',
        'cta_url',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function fromBusiness(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'from_business_id');
    }

    public function toBusiness(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'to_business_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('priority');
    }

    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('from_business_id', $businessId);
    }

    public function scopeForIntent(Builder $query, string $intent): Builder
    {
        return $query->where('trigger_intent', $intent);
    }
}
