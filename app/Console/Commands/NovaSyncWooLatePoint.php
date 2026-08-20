<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaIntegrationSetting;
use App\Services\Nova\NovaWooCommerceApiSyncService;
use App\Services\Nova\NovaWooLatePointDatabaseSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

final class NovaSyncWooLatePoint extends Command
{
    protected $signature = 'nova:sync-woo-latepoint {--integration= : Integration setting ID} {--business= : Business slug} {--full : Run full sync}';

    protected $description = 'Sync Nova external WooCommerce and LatePoint data from configured WordPress databases';

    public function handle(NovaWooLatePointDatabaseSyncService $databaseSyncService, NovaWooCommerceApiSyncService $apiSyncService): int
    {
        if (! Schema::hasTable('nova_integration_settings')) {
            $this->warn('Nova integration tables are not migrated yet.');

            return self::SUCCESS;
        }

        $settings = $this->settings();

        if ($settings->isEmpty()) {
            $this->warn('No active Woo/LatePoint database integrations found.');

            return self::SUCCESS;
        }

        foreach ($settings as $setting) {
            $this->info("Syncing {$setting->name} ({$setting->source_type})...");
            $summary = $setting->connection_type === 'api'
                ? $apiSyncService->sync($setting, (bool) $this->option('full'))
                : $databaseSyncService->sync($setting, (bool) $this->option('full'));

            $this->table(['Metric', 'Value'], collect($summary)->map(fn ($value, string $key): array => [$key, (string) $value])->values()->all());
        }

        return self::SUCCESS;
    }

    private function settings(): Collection
    {
        return NovaIntegrationSetting::query()
            ->active()
            ->whereIn('source_type', ['woo', 'latepoint', 'wordpress', 'woo_latepoint'])
            ->when($this->option('integration'), fn ($query, mixed $id) => $query->whereKey($id))
            ->when($this->option('business'), fn ($query, mixed $slug) => $query->whereHas('business', fn ($businessQuery) => $businessQuery->where('slug', $slug)))
            ->get();
    }
}
