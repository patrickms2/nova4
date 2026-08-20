<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaIntegrationSetting;
use App\Services\Nova\NovaMagentoApiSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class NovaSyncMagento extends Command
{
    protected $signature = 'nova:sync-magento {--integration= : Integration setting ID} {--business= : Business slug} {--full : Run full sync}';

    protected $description = 'Sync Nova external Magento products and orders from configured Magento APIs';

    public function handle(NovaMagentoApiSyncService $syncService): int
    {
        if (! Schema::hasTable('nova_integration_settings')) {
            $this->warn('Nova integration tables are not migrated yet.');

            return self::SUCCESS;
        }

        $settings = $this->settings();

        if ($settings->isEmpty()) {
            $this->warn('No active Magento API integrations found.');

            return self::SUCCESS;
        }

        foreach ($settings as $setting) {
            $this->info("Syncing {$setting->name}...");
            $summary = $syncService->sync($setting, (bool) $this->option('full'));

            $this->table(['Metric', 'Value'], collect($summary)->map(fn ($value, string $key): array => [$key, (string) $value])->values()->all());
        }

        return self::SUCCESS;
    }

    private function settings(): Collection
    {
        return NovaIntegrationSetting::query()
            ->active()
            ->where('connection_type', 'api')
            ->where('source_type', 'magento')
            ->when($this->option('integration'), fn ($query, mixed $id) => $query->whereKey($id))
            ->when($this->option('business'), fn ($query, mixed $slug) => $query->whereHas('business', fn ($businessQuery) => $businessQuery->where('slug', $slug)))
            ->get();
    }
}
