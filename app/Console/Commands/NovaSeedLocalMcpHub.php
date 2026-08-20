<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaBusiness;
use App\Models\NovaIntegrationSetting;
use App\Models\Server;
use App\Models\NovaService;
use Illuminate\Console\Command;

final class NovaSeedLocalMcpHub extends Command
{
    protected $signature = 'nova:seed-local-mcp-hub {--dry-run : Show local MCP hub data without writing}';

    protected $description = 'Seed local Nova businesses, services and MCP servers for local hub testing';

    public function handle(): int
    {
        $definitions = $this->definitions();

        if ((bool) $this->option('dry-run')) {
            foreach ($definitions as $definition) {
                $this->line("Would seed {$definition['business']['name']} ({$definition['business']['slug']}).");

                foreach ($definition['services'] as $service) {
                    $this->line("  service: {$service['name']} [{$service['code']}]");
                }

                foreach ($definition['mcp_servers'] as $server) {
                    $this->line("  mcp: {$server['name']} -> {$server['endpoint_url']}");
                }
            }

            return self::SUCCESS;
        }

        $servicesByBusinessAndCode = [];

        foreach ($definitions as $definition) {
            $business = $this->upsertBusiness($definition['business']);
            $this->info("Business: {$business->name}");

            foreach ($definition['services'] as $serviceDefinition) {
                $service = $this->upsertService($business, $serviceDefinition);
                $servicesByBusinessAndCode[$business->slug][$service->code] = $service;

                $this->line("  Service: {$service->name}");
            }

            foreach ($definition['mcp_servers'] as $serverDefinition) {
                $service = $servicesByBusinessAndCode[$business->slug][$serverDefinition['service_code']] ?? null;
                $server = $this->upsertMcpServer($business, $service, $serverDefinition);

                $this->line("  MCP: {$server->name}");
            }
        }

        $this->fixLanzaloeMagentoIntegration();

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function upsertBusiness(array $definition): NovaBusiness
    {
        $business = NovaBusiness::query()
            ->where('slug', $definition['slug'])
            ->orWhereIn('slug', $definition['match_slugs'] ?? [])
            ->orWhereIn('name', $definition['match_names'] ?? [])
            ->first();

        if (! $business) {
            $business = new NovaBusiness();
        }

        $business->fill([
            'name' => $definition['name'],
            'slug' => $definition['slug'],
            'business_type' => $definition['business_type'],
            'status' => 'active',
            'website_url' => $definition['website_url'],
            'settings' => array_replace_recursive($business->settings ?? [], $definition['settings']),
        ]);

        $business->save();

        return $business;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function upsertService(NovaBusiness $business, array $definition): NovaService
    {
        $service = NovaService::query()
            ->firstOrNew([
                'nova_business_id' => $business->id,
                'code' => $definition['code'],
            ]);

        $service->fill([
            'name' => $definition['name'],
            'service_type' => $definition['service_type'],
            'status' => 'active',
            'has_development' => $definition['has_development'] ?? false,
            'has_maintenance' => $definition['has_maintenance'] ?? false,
            'has_whatsapp' => $definition['has_whatsapp'] ?? false,
            'has_mcp' => $definition['has_mcp'] ?? true,
            'has_sales' => $definition['has_sales'] ?? false,
            'has_services' => $definition['has_services'] ?? true,
            'settings' => array_replace_recursive($service->settings ?? [], $definition['settings'] ?? []),
            'notes' => $definition['notes'] ?? null,
        ]);

        $service->save();

        return $service;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function upsertMcpServer(NovaBusiness $business, ?NovaService $service, array $definition): Server
    {
        $names = array_values(array_unique(array_merge(
            [$definition['name']],
            $definition['match_names'] ?? [],
        )));

        $server = Server::query()
            ->where('nova_business_id', $business->id)
            ->where('type', $definition['type'])
            ->whereIn('name', $names)
            ->orderBy('id')
            ->first() ?? new Server([
                'nova_business_id' => $business->id,
                'type' => $definition['type'],
            ]);

        $server->fill([
            'name' => $definition['name'],
            'nova_service_id' => $service?->id,
            'endpoint' => $definition['endpoint_url'],
            'auth_type' => $definition['auth_type'] ?? 'none',
            'status' => 'active',
            'capabilities' => $definition['capabilities'],
            'credentials' => $definition['credentials'] ?? [],
            'last_error' => null,
        ]);

        $server->save();

        Server::query()
            ->where('nova_business_id', $business->id)
            ->where('type', $definition['type'])
            ->whereIn('name', $names)
            ->whereKeyNot($server->id)
            ->delete();

        return $server;
    }

    private function fixLanzaloeMagentoIntegration(): void
    {
        $business = NovaBusiness::query()->where('slug', 'lanzaloe')->first();
        $service = $business?->services()->where('code', 'magento-mcp')->first();

        if (! $business || ! $service) {
            return;
        }

        NovaIntegrationSetting::query()
            ->where('name', 'Lanzaloe Magento API')
            ->update([
                'nova_business_id' => $business->id,
                'nova_service_id' => $service->id,
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        $sirvoEndpoint = rtrim((string) env('SIRVO_ENDPOINT_URL', 'http://192.168.1.42:3000'), '/');
        $lageriaEndpoint = rtrim((string) env('LAGERIA_ENDPOINT_URL', 'https://lageriawp.test'), '/');
        $taxilanzEndpoint = rtrim((string) env('TAXILANZ_ENDPOINT_URL', 'https://taxilanzwp.test'), '/');
        $taxilanzHotelesEndpoint = rtrim((string) env('TAXILANZ_HOTELES_ENDPOINT_URL', 'https://taxilanzhrnew.test/api/mcp'), '/');
        $lanzaloeEndpoint = rtrim((string) env('LANZALOE_ENDPOINT_URL', 'https://lanzaloe.novagestion.eu'), '/');

        return [
            [
                'business' => [
                    'name' => 'Sirvo',
                    'slug' => 'sirvo',
                    'business_type' => 'restaurant',
                    'website_url' => $sirvoEndpoint,
                    'settings' => [
                        'local_path' => env('SIRVO_LOCAL_PATH', '/Users/patrickms/Sites/localhost/sirvo'),
                        'stack' => ['sirvo', 'restaurants', 'mcp'],
                    ],
                ],
                'services' => [
                    [
                        'name' => 'Sirvo Restaurantes MCP',
                        'code' => 'sirvo-restaurants-mcp',
                        'service_type' => 'services',
                        'has_mcp' => true,
                        'has_services' => true,
                        'settings' => ['domain' => 'restaurants'],
                    ],
                ],
                'mcp_servers' => [
                    [
                        'name' => 'Sirvo Restaurantes MCP',
                        'match_names' => ['Sirvo Local API'],
                        'type' => 'sirvo',
                        'service_code' => 'sirvo-restaurants-mcp',
                        'endpoint_url' => $sirvoEndpoint,
                        'capabilities' => [
                            'config' => '/api/config',
                            'branches' => '/api/branches',
                            'reservations' => '/api/reservations',
                            'dashboard_reservations' => '/api/dashboard/reservations',
                            'chat' => '/api/chat',
                        ],
                    ],
                ],
            ],
            [
                'business' => [
                    'name' => 'La Geria',
                    'slug' => 'la-geria',
                    'business_type' => 'winery',
                    'website_url' => $lageriaEndpoint,
                    'settings' => [
                        'local_path' => env('LAGERIA_LOCAL_PATH', '/Users/patrickms/Downloads/lageria'),
                        'stack' => ['wordpress', 'woocommerce', 'latepoint', 'mcp'],
                    ],
                ],
                'services' => [
                    [
                        'name' => 'La Geria WordPress + Woo + LatePoint',
                        'code' => 'wp-woo-latepoint-mcp',
                        'service_type' => 'services',
                        'has_mcp' => true,
                        'has_sales' => true,
                        'has_services' => true,
                        'settings' => ['domain' => 'winery_visits'],
                    ],
                ],
                'mcp_servers' => [
                    [
                        'name' => 'La Geria WordPress Woo LatePoint MCP',
                        'match_names' => ['La Geria WordPress MCP'],
                        'type' => 'la_geria',
                        'service_code' => 'wp-woo-latepoint-mcp',
                        'endpoint_url' => $lageriaEndpoint,
                        'capabilities' => [
                            'wordpress_rest' => '/wp-json/',
                            'woocommerce_orders' => '/wp-json/wc/v3/orders',
                            'latepoint_bookings' => '/wp-json/',
                            'latepoint_invoices' => '/wp-json/',
                            'abilities' => '/wp-json/abilities/v1',
                            'mcp' => '/wp-json/mcp/v1',
                        ],
                    ],
                ],
            ],
            [
                'business' => [
                    'name' => 'Taxilanz Rutas',
                    'slug' => 'taxilanz-rutas',
                    'business_type' => 'taxi',
                    'website_url' => $taxilanzEndpoint,
                    'settings' => [
                        'local_path' => env('TAXILANZ_RUTAS_LOCAL_PATH', '/Users/patrickms/Downloads/tourist'),
                        'stack' => ['wordpress', 'woocommerce', 'routes', 'mcp'],
                    ],
                ],
                'services' => [
                    [
                        'name' => 'Taxilanz Woo + Rutas',
                        'code' => 'woo-routes-mcp',
                        'service_type' => 'sales',
                        'has_mcp' => true,
                        'has_sales' => true,
                        'has_services' => true,
                        'settings' => ['domain' => 'taxi_routes'],
                    ],
                ],
                'mcp_servers' => [
                    [
                        'name' => 'Taxilanz Rutas Woo MCP',
                        'type' => 'taxilanz',
                        'service_code' => 'woo-routes-mcp',
                        'endpoint_url' => $taxilanzEndpoint,
                        'capabilities' => [
                            'wordpress_rest' => '/wp-json/',
                            'woocommerce_orders' => '/wp-json/wc/v3/orders',
                            'routes' => '/wp-json/',
                            'customers' => '/wp-json/wc/v3/customers',
                            'payments' => '/wp-json/wc/v3/orders',
                        ],
                    ],
                ],
            ],
            [
                'business' => [
                    'name' => 'Taxilanz Hoteles',
                    'slug' => 'taxilanz-hoteles',
                    'business_type' => 'hotel',
                    'website_url' => $taxilanzHotelesEndpoint,
                    'settings' => [
                        'local_path' => env('TAXILANZ_HOTELES_LOCAL_PATH', '/Users/patrickms/Downloads/taxilanzhrnew'),
                        'stack' => ['laravel', 'filament', 'mcp'],
                    ],
                ],
                'services' => [
                    [
                        'name' => 'Taxilanz Hoteles Laravel MCP',
                        'code' => 'laravel-hotels-mcp',
                        'service_type' => 'services',
                        'has_development' => true,
                        'has_maintenance' => true,
                        'has_mcp' => true,
                        'has_services' => true,
                        'settings' => ['domain' => 'hotels'],
                    ],
                ],
                'mcp_servers' => [
                    [
                        'name' => 'Taxilanz Hoteles Laravel MCP',
                        'type' => 'taxilanz_hoteles',
                        'service_code' => 'laravel-hotels-mcp',
                        'endpoint_url' => $taxilanzHotelesEndpoint,
                        'capabilities' => [
                            'info' => '/info',
                            'tools' => '/tools',
                            'execute' => '/execute',
                            'filament' => '/app',
                            'api' => '/api',
                        ],
                    ],
                ],
            ],
            [
                'business' => [
                    'name' => 'Lanzaloe',
                    'slug' => 'lanzaloe',
                    'match_slugs' => ['lanzaloe'],
                    'match_names' => ['Lazaloe'],
                    'business_type' => 'magento',
                    'website_url' => $lanzaloeEndpoint,
                    'settings' => [
                        'stack' => ['magento', 'mcp'],
                    ],
                ],
                'services' => [
                    [
                        'name' => 'Lanzaloe Magento MCP',
                        'code' => 'magento-mcp',
                        'service_type' => 'sales',
                        'has_mcp' => true,
                        'has_sales' => true,
                        'has_services' => true,
                        'settings' => ['domain' => 'ecommerce'],
                    ],
                ],
                'mcp_servers' => [
                    [
                        'name' => 'Lanzaloe Magento MCP',
                        'type' => 'lanzaloe',
                        'service_code' => 'magento-mcp',
                        'endpoint_url' => $lanzaloeEndpoint,
                        'capabilities' => [
                            'orders' => '/rest/all/V1/orders',
                            'products' => '/rest/all/V1/products',
                            'customers' => '/rest/all/V1/customers',
                            'stock' => '/rest/all/V1/stockItems',
                            'sync' => '/rest/all/V1',
                        ],
                    ],
                ],
            ],
        ];
    }
}
