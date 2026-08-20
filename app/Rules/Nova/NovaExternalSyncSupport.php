<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaExternalCustomer;
use App\Models\NovaIntegrationLink;
use App\Models\NovaIntegrationSetting;
use App\Models\NovaIntegrationSyncLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NovaExternalSyncSupport
{
    public function computeSyncSince(NovaIntegrationSetting $setting, bool $fullSync = false): CarbonImmutable
    {
        if ($fullSync) {
            return now()->subYears(5)->toImmutable();
        }

        return ($setting->last_sync_finished_at ?? now()->subHours(6))->toImmutable()->subMinutes(10);
    }

    public function externalConnectionName(NovaIntegrationSetting $setting): string
    {
        return $setting->external_db_connection ?: 'nova_external_sync_'.$setting->id;
    }

    public function applyExternalDatabaseConfig(NovaIntegrationSetting $setting): string
    {
        $connection = $this->externalConnectionName($setting);
        $driver = $setting->external_db_driver ?: 'mysql';
        $fallback = Config::get("database.connections.{$driver}", Config::get('database.connections.mysql'));

        Config::set("database.connections.{$connection}", array_merge($fallback, [
            'driver' => $driver,
            'host' => $setting->external_db_host,
            'port' => $setting->external_db_port ?: ($driver === 'pgsql' ? '5432' : '3306'),
            'database' => $setting->external_db_database,
            'username' => $setting->external_db_username,
            'password' => $setting->getDecryptedExternalDbPassword(),
        ]));

        DB::purge($connection);

        return $connection;
    }

    public function markSyncStarted(NovaIntegrationSetting $setting): void
    {
        $setting->forceFill([
            'last_sync_started_at' => now(),
            'last_sync_error' => null,
        ])->save();
    }

    public function markSyncFinished(NovaIntegrationSetting $setting): void
    {
        $setting->forceFill([
            'last_sync_finished_at' => now(),
            'last_sync_failed_at' => null,
            'last_sync_error' => null,
        ])->save();
    }

    public function markSyncFailed(NovaIntegrationSetting $setting, \Throwable $exception): void
    {
        $setting->forceFill([
            'last_sync_failed_at' => now(),
            'last_sync_error' => Str::limit($exception->getMessage(), 5000),
        ])->save();
    }

    public function logSync(NovaIntegrationSetting $setting, string $jobName, string $entityType, array $summary, string $status = 'ok', ?\Throwable $exception = null): void
    {
        NovaIntegrationSyncLog::query()->create([
            'nova_integration_setting_id' => $setting->id,
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'source' => $setting->source_type,
            'job_name' => $jobName,
            'entity_type' => $entityType,
            'status' => $status,
            'processed_count' => (int) ($summary['processed'] ?? 0),
            'created_count' => (int) ($summary['created'] ?? 0),
            'updated_count' => (int) ($summary['updated'] ?? 0),
            'error_message' => $exception?->getMessage(),
            'metadata' => Arr::except($summary, ['processed', 'created', 'updated']),
            'processed_at' => now(),
        ]);
    }

    public function upsertCustomer(NovaIntegrationSetting $setting, array $payload): ?NovaExternalCustomer
    {
        if (blank($payload['name'] ?? null) && blank($payload['email'] ?? null) && blank($payload['phone'] ?? null) && blank($payload['external_id'] ?? null)) {
            return null;
        }

        $customer = null;

        if (filled($payload['external_id'] ?? null)) {
            $customer = NovaExternalCustomer::query()
                ->where('nova_business_id', $setting->nova_business_id)
                ->where('source', $payload['source'])
                ->where('external_id', $payload['external_id'])
                ->first();
        }

        if (! $customer && filled($payload['email'] ?? null)) {
            $customer = NovaExternalCustomer::query()
                ->where('nova_business_id', $setting->nova_business_id)
                ->where('email', $payload['email'])
                ->first();
        }

        $customer ??= new NovaExternalCustomer;
        $customer->fill(array_merge($payload, [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'last_synced_at' => now(),
        ]));
        $customer->save();

        return $customer;
    }

    public function upsertIntegrationLink(Model $model, NovaIntegrationSetting $setting, string $source, string $externalId, ?string $externalItemId = null, ?string $url = null, ?string $intentKey = null, ?array $metadata = null, mixed $sourceUpdatedAt = null): void
    {
        if (blank($externalId)) {
            return;
        }

        NovaIntegrationLink::query()->updateOrCreate(
            [
                'linkable_type' => $model::class,
                'linkable_id' => $model->id,
                'source' => $source,
                'external_id' => $externalId,
                'external_item_id' => $externalItemId,
            ],
            [
                'nova_integration_setting_id' => $setting->id,
                'intent_key' => $intentKey,
                'url' => $url,
                'metadata' => $metadata,
                'source_updated_at' => $sourceUpdatedAt,
            ],
        );
    }
}
