<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McpLog extends Model
{
    protected $fillable = [
        'server_id', 'tool_id', 'type', 'method',
        'request_data', 'response_data', 'error_message',
        'duration_ms', 'client_info', 'ip_address',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
