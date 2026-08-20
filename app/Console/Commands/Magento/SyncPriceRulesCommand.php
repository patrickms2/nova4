<?php

namespace App\Console\Commands\Magento;

use App\Models\MagentoPriceRule;
use App\Models\MagentoStore;
use App\Models\Tenant;
use App\Services\Magento\MagentoApiClient;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SyncPriceRulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magento:sync-price-rules {tenant? : The ID of the tenant to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync price rules (Catalog & Cart) from Magento';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Magento price rules sync.');

        $tenants = $this->getTenants();

        $tenants->each(fn(Tenant $tenant) => $this->syncTenant($tenant));

        $this->info('Price rules sync completed successfully.');

        return self::SUCCESS;
    }

    protected function syncTenant(Tenant $tenant): void
    {
        $this->info("Syncing price rules for tenant: {$tenant->name}");
        $tenant->magentoStores()->each(fn(MagentoStore $store) => $this->syncStore($store));
    }

    protected function syncStore(MagentoStore $store): void
    {
        $this->info("Syncing price rules for store: {$store->name} ({$store->base_url})");
        $apiClient = new MagentoApiClient($store);

        $user = $store->tenant->users()->first();
        if (!$user) {
            $this->error("No users found for tenant: {$store->tenant->name}");
            return;
        }

        Auth::login($user);
        Filament::setTenant($store->tenant);

        // Sync Catalog Rules
        $this->syncCatalogRules($store, $apiClient);

        // Sync Cart Rules
        $this->syncCartRules($store, $apiClient);

        Filament::setTenant(null);
        Auth::logout();
    }

    protected function syncCatalogRules(MagentoStore $store, MagentoApiClient $apiClient): void
    {
        $this->info("Fetching Catalog Price Rules...");
        try {
            $rulesResponse = $apiClient->getCatalogRules();
            $items = $rulesResponse['items'] ?? [];
            $count = 0;

            foreach ($items as $ruleData) {
                MagentoPriceRule::updateOrCreate(
                    [
                        'tenant_id' => $store->tenant_id,
                        'magento_rule_id' => $ruleData['rule_id'],
                        'rule_type' => 'catalog',
                    ],
                    [
                        'name' => $ruleData['name'],
                        'is_active' => (bool)($ruleData['is_active'] ?? true),
                        'raw_data' => $ruleData,
                    ]
                );
                $count++;
            }
            $this->info("Synced {$count} catalog price rules.");
        } catch (\Exception $e) {
            $this->error("Failed to sync catalog rules: " . $e->getMessage());
        }
    }

    protected function syncCartRules(MagentoStore $store, MagentoApiClient $apiClient): void
    {
        $this->info("Fetching Cart Price Rules (Sales Rules)...");
        try {
            $rulesResponse = $apiClient->getCartRules();
            $items = $rulesResponse['items'] ?? [];
            $count = 0;

            foreach ($items as $ruleData) {
                MagentoPriceRule::updateOrCreate(
                    [
                        'tenant_id' => $store->tenant_id,
                        'magento_rule_id' => $ruleData['rule_id'],
                        'rule_type' => 'cart',
                    ],
                    [
                        'name' => $ruleData['name'],
                        'is_active' => (bool)($ruleData['is_active'] ?? true),
                        'raw_data' => $ruleData,
                    ]
                );
                $count++;
            }
            $this->info("Synced {$count} cart price rules.");
        } catch (\Exception $e) {
            $this->error("Failed to sync cart rules: " . $e->getMessage());
        }
    }

    protected function getTenants(): Collection
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return new Collection;
            }
            return new Collection([$tenant]);
        }

        return Tenant::with('magentoStores')->get();
    }
}
