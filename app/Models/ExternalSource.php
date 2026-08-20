<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'name',
        'business_name',
        'source_platform',
        'source_label',
        'resource_type',
        'target_model',
        'sync_direction',
        'capability',
        'connection_type',
        'base_url',
        'api_url',
        'external_db_connection',
        'external_db_driver',
        'external_db_host',
        'external_db_port',
        'external_db_database',
        'external_db_username',
        'external_db_password',
        'external_db_prefix',
        'credentials',
        'settings',
        'status',
        'last_sync_started_at',
        'last_sync_finished_at',
        'last_sync_failed_at',
        'last_sync_error',
    ];

    protected $casts = [
        'credentials' => 'array',
        'settings' => 'array',
        'last_sync_started_at' => 'datetime',
        'last_sync_finished_at' => 'datetime',
        'last_sync_failed_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(ExternalCatalogItem::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ExternalBooking::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ExternalOrder::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(ExternalSyncLog::class);
    }

    public function syncMappings(): HasMany
    {
        return $this->hasMany(ExternalSyncMapping::class);
    }
}
