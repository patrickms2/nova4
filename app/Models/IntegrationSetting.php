<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'external_db_connection',
        'external_db_host',
        'external_db_port',
        'external_db_database',
        'external_db_username',
        'external_db_password',
        'external_db_prefix',
        'wordpress_base_url',
        'woocommerce_admin_path',
        'latepoint_admin_booking_path',
        'sync_latepoint_enabled',
        'sync_woocommerce_enabled',
        'sync_interval_minutes',
        'sync_window_hours',
        'last_sync_started_at',
        'last_sync_finished_at',
        'last_sync_failed_at',
        'last_sync_error',
    ];

    protected $attributes = [
        'name' => 'WooCommerce + LatePoint',
        'is_active' => true,
        'external_db_connection' => 'wordpress_sync',
        'external_db_host' => '127.0.0.1',
        'external_db_port' => 3306,
        'external_db_prefix' => 'th_',
        'sync_latepoint_enabled' => true,
        'sync_woocommerce_enabled' => true,
        'sync_interval_minutes' => 5,
        'sync_window_hours' => 24,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sync_latepoint_enabled' => 'boolean',
            'sync_woocommerce_enabled' => 'boolean',
            'external_db_port' => 'integer',
            'sync_interval_minutes' => 'integer',
            'sync_window_hours' => 'integer',
            'last_sync_started_at' => 'datetime',
            'last_sync_finished_at' => 'datetime',
            'last_sync_failed_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getDecryptedPassword(): ?string
    {
        if (blank($this->external_db_password)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->external_db_password);
        } catch (\Throwable) {
            return $this->external_db_password;
        }
    }
}
