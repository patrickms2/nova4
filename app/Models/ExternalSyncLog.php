<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_source_id',
        'server_id',
        'command',
        'sync_type',
        'status',
        'processed',
        'created',
        'updated',
        'skipped',
        'summary',
        'error',
    ];

    protected $casts = [
        'processed' => 'integer',
        'created' => 'integer',
        'updated' => 'integer',
        'skipped' => 'integer',
        'summary' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function externalSource(): BelongsTo
    {
        return $this->belongsTo(ExternalSource::class);
    }
}
