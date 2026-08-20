<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntegrationLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'linkable_type',
        'linkable_id',
        'source',
        'external_id',
        'external_item_id',
        'intent_key',
        'url',
        'meta',
        'source_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'source_updated_at' => 'datetime',
        ];
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
