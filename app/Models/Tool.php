<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id', 'name', 'title', 'description',
        'input_schema', 'output_schema', 'handler_code',
        'annotations', 'metadata', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'input_schema' => 'array',
        'output_schema' => 'array',
        'annotations' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(McpLog::class);
    }

    public function getAnnotationAttribute(string $key): bool
    {
        return $this->annotations[$key] ?? false;
    }
}
