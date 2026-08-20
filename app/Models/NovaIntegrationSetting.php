<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

final class NovaIntegrationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'nova_business_id',
        'nova_service_id',
        'name',
        'source_type',
        'connection_type',
        'status',
        'base_url',
        'api_url',
        'auth_type',
        'credentials',
        'external_db_connection',
        'external_db_driver',
        'external_db_host',
        'external_db_port',
        'external_db_database',
        'external_db_username',
        'external_db_password',
        'external_db_prefix',
        'last_sync_started_at',
        'last_sync_finished_at',
        'last_sync_failed_at',
        'last_sync_error',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'last_sync_started_at' => 'datetime',
            'last_sync_finished_at' => 'datetime',
            'last_sync_failed_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    /**
     * @return Attribute<array<string, mixed>|null, array<string, mixed>|null>
     */
    protected function credentials(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): ?array {
                if (blank($value)) {
                    return null;
                }

                try {
                    $decrypted = Crypt::decryptString((string) $value);
                    $decoded = json_decode($decrypted, true);

                    return is_array($decoded) ? $decoded : null;
                } catch (DecryptException) {
                    $decoded = json_decode((string) $value, true);

                    return is_array($decoded) ? $decoded : null;
                }
            },
            set: fn (?array $value): ?string => $value === null
                ? null
                : Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR)),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(NovaService::class, 'nova_service_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(NovaIntegrationSyncLog::class);
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(NovaExternalCatalogItem::class);
    }

    public function getDecryptedExternalDbPassword(): ?string
    {
        if (blank($this->external_db_password)) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $this->external_db_password);
        } catch (\Throwable) {
            return (string) $this->external_db_password;
        }
    }
}
