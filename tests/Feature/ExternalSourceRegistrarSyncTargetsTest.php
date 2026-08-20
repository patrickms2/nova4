<?php

namespace Tests\Feature;

use App\Models\ExternalSource;
use App\Models\Server;
use App\Services\ExternalSync\ExternalSourceRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalSourceRegistrarSyncTargetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_one_source_per_explicit_sync_target(): void
    {
        $server = Server::query()->create([
            'name' => 'Taxilanz Rutas Woo MCP',
            'slug' => 'taxilanz-rutas-woo',
            'metadata' => [
                'business' => 'Taxilanz',
                'remote_endpoint' => 'https://taxilanzwp.test',
                'source_stack' => ['wordpress', 'woocommerce', 'routes', 'mcp'],
                'sync_targets' => [
                    [
                        'capability' => 'woocommerce_products',
                        'source_platform' => 'woo',
                        'resource_type' => 'tour_route',
                        'target_model' => 'tour',
                        'source_label_suffix' => 'Rutas',
                    ],
                    [
                        'capability' => 'woocommerce_orders',
                        'source_platform' => 'woo',
                        'resource_type' => 'tour_booking',
                        'target_model' => 'tour_booking',
                        'source_label_suffix' => 'Reservas rutas',
                    ],
                ],
            ],
        ]);

        $sources = app(ExternalSourceRegistrar::class)->registerForServer($server);

        $this->assertCount(2, $sources);
        $this->assertDatabaseHas('external_sources', [
            'server_id' => $server->id,
            'business_name' => 'Taxilanz',
            'source_platform' => 'woo',
            'source_label' => 'Taxilanz · Woo · Rutas',
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'sync_direction' => 'remote_to_local',
            'capability' => 'woocommerce_products',
        ]);
        $this->assertDatabaseHas('external_sources', [
            'server_id' => $server->id,
            'source_label' => 'Taxilanz · Woo · Reservas rutas',
            'resource_type' => 'tour_booking',
            'target_model' => 'tour_booking',
            'capability' => 'woocommerce_orders',
        ]);
    }

    public function test_fallback_source_stack_registration_still_works(): void
    {
        $server = Server::query()->create([
            'name' => 'Lanzaloe Magento MCP',
            'slug' => 'lanzaloe-magento',
            'metadata' => [
                'business' => 'Lanzaloe',
                'source_stack' => ['magento', 'mcp'],
            ],
        ]);

        app(ExternalSourceRegistrar::class)->registerForServer($server);

        $source = ExternalSource::query()->sole();
        $this->assertSame('Lanzaloe · Magento', $source->source_label);
        $this->assertSame('generic_product', $source->resource_type);
        $this->assertSame('external_catalog_item', $source->target_model);
    }

    public function test_registering_explicit_sync_targets_pauses_stale_fallback_sources(): void
    {
        $server = Server::query()->create([
            'name' => 'Taxilanz Rutas Woo MCP',
            'slug' => 'taxilanz-rutas-woo',
            'metadata' => [
                'business' => 'Taxilanz Rutas',
                'remote_endpoint' => 'https://taxilanzwp.test',
                'source_stack' => ['wordpress', 'woocommerce', 'mcp'],
            ],
        ]);

        app(ExternalSourceRegistrar::class)->registerForServer($server);

        $this->assertDatabaseHas('external_sources', [
            'server_id' => $server->id,
            'source_label' => 'Taxilanz Rutas · Woo',
            'status' => 'active',
        ]);

        $server->forceFill([
            'metadata' => [
                'business' => 'Taxilanz Rutas',
                'remote_endpoint' => 'https://taxilanzwp.test',
                'source_stack' => ['wordpress', 'woocommerce', 'chauffeur-booking-system', 'mcp'],
                'sync_targets' => [
                    [
                        'capability' => 'chauffeur_routes',
                        'source_platform' => 'woo',
                        'resource_type' => 'tour_route',
                        'target_model' => 'tour',
                        'source_label_suffix' => 'Rutas Chauffeur',
                    ],
                ],
            ],
        ])->save();

        app(ExternalSourceRegistrar::class)->registerForServer($server);

        $this->assertDatabaseHas('external_sources', [
            'server_id' => $server->id,
            'source_label' => 'Taxilanz Rutas · Woo',
            'status' => 'paused',
        ]);
        $this->assertDatabaseHas('external_sources', [
            'server_id' => $server->id,
            'source_label' => 'Taxilanz Rutas · Woo · Rutas Chauffeur',
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'capability' => 'chauffeur_routes',
            'status' => 'active',
        ]);
    }

    public function test_sync_target_status_can_pause_source_registration(): void
    {
        $server = Server::query()->create([
            'name' => 'Taxilanz Rutas Woo MCP',
            'slug' => 'taxilanz-rutas-woo',
            'metadata' => [
                'business' => 'Taxilanz Rutas',
                'remote_endpoint' => 'https://taxilanzwp.test',
                'source_stack' => ['wordpress', 'woocommerce', 'mcp'],
                'sync_targets' => [
                    [
                        'capability' => 'routes',
                        'source_platform' => 'woo',
                        'resource_type' => 'tour_route',
                        'target_model' => 'tour',
                        'source_label_suffix' => 'Rutas',
                        'status' => 'paused',
                    ],
                ],
            ],
        ]);

        app(ExternalSourceRegistrar::class)->registerForServer($server);

        $this->assertDatabaseHas('external_sources', [
            'server_id' => $server->id,
            'source_label' => 'Taxilanz Rutas · Woo · Rutas',
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'capability' => 'routes',
            'status' => 'paused',
        ]);
    }
}
