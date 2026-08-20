<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id', 'name', 'title', 'description',
        'arguments', 'messages', 'metadata', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'arguments' => 'array',
        'messages' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
