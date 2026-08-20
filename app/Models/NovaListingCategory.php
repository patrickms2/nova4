<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovaListingCategory extends Model
{
    protected $fillable = [
        'nova_business_id',
        'server_id',
        'tool_id',
        'slug',
        'keywords',
        'system_names',
        'intro_text',
        'cta_text',
        'count_label',
        'tool_params',
        'item_fields',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'system_names' => 'array',
            'tool_params' => 'array',
            'item_fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function matchesMessage(string $message): bool
    {
        $msg = mb_strtolower($message);
        foreach ($this->keywords ?? [] as $kw) {
            if (str_contains($msg, mb_strtolower((string) $kw))) {
                return true;
            }
        }

        return false;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
