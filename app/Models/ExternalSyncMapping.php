<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalSyncMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'external_source_id',
        'business_name',
        'source_platform',
        'source_label',
        'resource_type',
        'target_model',
        'target_id',
        'external_id',
        'external_item_id',
        'payload_hash',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ExternalSource::class, 'external_source_id');
    }
}
