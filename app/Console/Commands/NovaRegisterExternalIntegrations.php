<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaBusiness;
use App\Models\NovaIntegrationSetting;
use App\Models\NovaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

final class NovaRegisterExternalIntegrations extends Command
{
    protected $signature = 'nova:register-external-integrations {--dry-run : Show what would be registered without writing}';

    protected $description = 'Register Nova external sync integrations from environment variables without printing secrets';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $definitions = array_filter($this->definitions(), fn (array $definition): bool => $this->isConfigured($definition));

        if ($definitions === []) {
            $this->warn('No external integration environment variables found.');
            $this->line('Supported groups: WOOCOMMERCE_*, NOVA_TAXILANZ_WOO_*, NOVA_LAGERIA_DB_*, NOVA_LANZALOE_MAGENTO_*.');

            return self::SUCCESS;
        }

        foreach ($definitions as $definition) {
            $business = $this->businessByTerms($definition['business_terms']);

            if (! $business) {
                $this->warn("Business not found for {$definition['name']}.");

                continue;
            }

            $service = $this->serviceForBusiness($business);
            $payload = $this->payload($definition, $business, $service);

            if ($dryRun) {
                $this->line("Would register {$definition['name']} for {$business->name}.");

                continue;
            }




            NovaIntegrationSetting::query()->updateOrCreate(
                [
                    'nova_business_id' => $business->id,
                    'name' => $definition['name'],
                    'source_type' => $definition['source_type'],
                    'connection_type' => $definition['connection_type'],
                ],
                $payload,
            );

            $this->info("Registered {$definition['name']} for {$business->name}.");
        }

        return self::SUCCESS;
    }

    private function isConfigured(array $definition): bool
    {
        foreach ($definition['required_env'] as $key) {
            if (blank(env($key))) {
                return false;
            }
        }

        return true;
    }

    private function payload(array $definition, NovaBusiness $business, ?NovaService $service): array
    {
        $payload = [
            'nova_service_id' => $service?->id,
            'status' => $definition['status'],
            'base_url' => $this->envString($definition['base_url_env'] ?? null),
            'api_url' => $this->envString($definition['api_url_env'] ?? null),
            'auth_type' => $definition['auth_type'],
            'credentials' => $this->credentials($definition['credential_env'] ?? []),
            'external_db_connection' => $this->envString($definition['db_connection_env'] ?? null),
            'external_db_driver' => $this->envString($definition['db_driver_env'] ?? null) ?: 'mysql',
            'external_db_host' => $this->envString($definition['db_host_env'] ?? null),
            'external_db_port' => $this->envString($definition['db_port_env'] ?? null),
            'external_db_database' => $this->envString($definition['db_database_env'] ?? null),
            'external_db_username' => $this->envString($definition['db_username_env'] ?? null),
            'external_db_prefix' => $this->envString($definition['db_prefix_env'] ?? null),
            'settings' => $definition['settings'],
        ];

        $password = $this->envString($definition['db_password_env'] ?? null);

        if ($password !== null) {
            $payload['external_db_password'] = Crypt::encryptString($password);
        }
dd($payload);

        return $payload;
    }

    private function credentials(array $envMap): array
    {
        $credentials = [];

        foreach ($envMap as $key => $envName) {
            $value = $this->envString($envName);

            if ($value !== null) {
                $credentials[$key] = $value;
            }
        }

        return $credentials;
    }

    private function envString(?string $key): ?string
    {
        if ($key === null || blank(env($key))) {
            return null;
        }

        return (string) env($key);
    }

    private function businessByTerms(array $terms): ?NovaBusiness
    {
        return NovaBusiness::query()
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query
                        ->orWhere('slug', 'like', '%'.$term.'%')
                        ->orWhere('name', 'like', '%'.$term.'%')
                        ->orWhere('business_type', 'like', '%'.$term.'%');
                }
            })
            ->first();
    }

    private function serviceForBusiness(NovaBusiness $business): ?NovaService
    {
        return $business->services()
            ->where(function ($query): void {
                $query
                    ->where('has_sales', true)
                    ->orWhere('has_services', true)
                    ->orWhere('has_mcp', true)
                    ->orWhere('has_whatsapp', true);
            })
            ->orderByRaw('has_sales desc, has_services desc, has_mcp desc, has_whatsapp desc')
            ->first() ?? $business->services()->first();
    }

    private function definitions(): array
    {
        return [
            [
                'name' => 'Taxilanz WooCommerce API',
                'source_type' => 'woo',
                'connection_type' => 'api',
                'status' => 'active',
                'business_terms' => ['taxilanz', 'taxi', 'lanaloe'],
                'required_env' => ['WOOCOMMERCE_STORE_URL', 'WOOCOMMERCE_CONSUMER_KEY', 'WOOCOMMERCE_CONSUMER_SECRET'],
                'base_url_env' => 'WOOCOMMERCE_STORE_URL',
                'api_url_env' => 'WOOCOMMERCE_STORE_URL',
                'auth_type' => 'consumer_key_secret',
                'credential_env' => [
                    'consumer_key' => 'WOOCOMMERCE_CONSUMER_KEY',
                    'consumer_secret' => 'WOOCOMMERCE_CONSUMER_SECRET',
                ],
                'settings' => [
                    'env_group' => 'WOOCOMMERCE',
                    'sync_note' => 'API credentials registered. DB sync requires NOVA_TAXILANZ_WOO_DB_* if needed.',
                ],
            ],
            [
                'name' => 'Taxilanz WooCommerce DB',
                'source_type' => 'woo',
                'connection_type' => 'database',
                'status' => 'active',
                'business_terms' => ['taxilanz', 'taxi', 'lanaloe'],
                'required_env' => ['NOVA_TAXILANZ_WOO_DB_HOST', 'NOVA_TAXILANZ_WOO_DB_DATABASE', 'NOVA_TAXILANZ_WOO_DB_USERNAME'],
                'base_url_env' => 'NOVA_TAXILANZ_WOO_URL',
                'auth_type' => 'database',
                'db_connection_env' => 'NOVA_TAXILANZ_WOO_DB_CONNECTION',
                'db_driver_env' => 'NOVA_TAXILANZ_WOO_DB_DRIVER',
                'db_host_env' => 'NOVA_TAXILANZ_WOO_DB_HOST',
                'db_port_env' => 'NOVA_TAXILANZ_WOO_DB_PORT',
                'db_database_env' => 'NOVA_TAXILANZ_WOO_DB_DATABASE',
                'db_username_env' => 'NOVA_TAXILANZ_WOO_DB_USERNAME',
                'db_password_env' => 'NOVA_TAXILANZ_WOO_DB_PASSWORD',
                'db_prefix_env' => 'NOVA_TAXILANZ_WOO_DB_PREFIX',
                'settings' => ['env_group' => 'NOVA_TAXILANZ_WOO_DB'],
            ],
            [
                'name' => 'La Geria Woo + LatePoint DB',
                'source_type' => 'woo_latepoint',
                'connection_type' => 'database',
                'status' => 'active',
                'business_terms' => ['la-geria', 'geria'],
                'required_env' => ['NOVA_LAGERIA_DB_HOST', 'NOVA_LAGERIA_DB_DATABASE', 'NOVA_LAGERIA_DB_USERNAME'],
                'base_url_env' => 'NOVA_LAGERIA_WORDPRESS_URL',
                'auth_type' => 'database',
                'db_connection_env' => 'NOVA_LAGERIA_DB_CONNECTION',
                'db_driver_env' => 'NOVA_LAGERIA_DB_DRIVER',
                'db_host_env' => 'NOVA_LAGERIA_DB_HOST',
                'db_port_env' => 'NOVA_LAGERIA_DB_PORT',
                'db_database_env' => 'NOVA_LAGERIA_DB_DATABASE',
                'db_username_env' => 'NOVA_LAGERIA_DB_USERNAME',
                'db_password_env' => 'NOVA_LAGERIA_DB_PASSWORD',
                'db_prefix_env' => 'NOVA_LAGERIA_DB_PREFIX',
                'settings' => [
                    'env_group' => 'NOVA_LAGERIA_DB',
                    'woocommerce_admin_path' => 'wp-admin/post.php?post={id}&action=edit',
                ],
            ],
            [
                'name' => 'Lanzaloe Magento API',
                'source_type' => 'magento',
                'connection_type' => 'api',
                'status' => 'active',
                'business_terms' => ['lanzaloe', 'aloe'],
                'required_env' => ['NOVA_LANZALOE_MAGENTO_URL', 'NOVA_LANZALOE_MAGENTO_TOKEN'],
                'base_url_env' => 'NOVA_LANZALOE_MAGENTO_URL',
                'api_url_env' => 'NOVA_LANZALOE_MAGENTO_URL',
                'auth_type' => 'bearer_token',
                'credential_env' => [
                    'access_token' => 'NOVA_LANZALOE_MAGENTO_TOKEN',
                ],
                'settings' => [
                    'env_group' => 'NOVA_LANZALOE_MAGENTO',
                    'currency' => 'EUR',
                    'page_size' => 200,
                ],
            ],
        ];
    }
}
