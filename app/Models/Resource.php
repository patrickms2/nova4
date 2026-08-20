<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id', 'name', 'title', 'description', 'uri',
        'uri_template', 'mime_type', 'content', 'handler_code',
        'annotations', 'metadata', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'annotations' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function isTemplate(): bool
    {
        return ! empty($this->uri_template);
    }

    public function isDynamic(): bool
    {
        return ! empty($this->handler_code);
    }
}
