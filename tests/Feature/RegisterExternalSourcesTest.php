<?php

namespace Tests\Feature;

use App\Models\ExternalSource;
use App\Models\Server;
use App\Services\ExternalSync\ExternalSourceRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterExternalSourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_external_sources_from_server_metadata(): void
    {
        $laGeria = Server::query()->create([
            'name' => 'La Geria WordPress Woo LatePoint MCP',
            'slug' => 'la-geria-wordpress-woo-latepoint',
            'metadata' => [
                'business' => 'La Geria',
                'source_stack' => ['wordpress', 'woocommerce', 'latepoint', 'mcp'],
                'remote_endpoint' => 'https://lageriawp.test',
            ],
        ]);

        $taxilanz = Server::query()->create([
            'name' => 'Taxilanz Rutas Woo MCP',
            'slug' => 'taxilanz-rutas-woo',
            'metadata' => [
                'business' => 'Taxilanz Rutas',
                'source_stack' => ['wordpress', 'woocommerce', 'routes', 'mcp'],
                'remote_endpoint' => 'https://taxilanz.test',
            ],
        ]);

        $lanzaloe = Server::query()->create([
            'name' => 'Lanzaloe Magento MCP',
            'slug' => 'lanzaloe-magento',
            'metadata' => [
                'business' => 'Lanzaloe',
                'source_stack' => ['magento', 'mcp'],
                'remote_endpoint' => 'https://lanzaloe.test',
            ],
        ]);

        $registrar = app(ExternalSourceRegistrar::class);

        $this->assertSame(2, $registrar->registerForServer($laGeria)->count());
        $this->assertSame(1, $registrar->registerForServer($taxilanz)->count());
        $this->assertSame(1, $registrar->registerForServer($lanzaloe)->count());

        $this->assertDatabaseHas('external_sources', [
            'server_id' => $laGeria->id,
            'business_name' => 'La Geria',
            'source_platform' => 'woo',
            'source_label' => 'La Geria · Woo',
            'connection_type' => 'api',
            'base_url' => 'https://lageriawp.test',
        ]);

        $this->assertDatabaseHas('external_sources', [
            'server_id' => $laGeria->id,
            'business_name' => 'La Geria',
            'source_platform' => 'latepoint',
            'source_label' => 'La Geria · LatePoint',
        ]);

        $this->assertDatabaseHas('external_sources', [
            'server_id' => $taxilanz->id,
            'business_name' => 'Taxilanz Rutas',
            'source_platform' => 'woo',
            'source_label' => 'Taxilanz Rutas · Woo',
        ]);

        $this->assertDatabaseHas('external_sources', [
            'server_id' => $lanzaloe->id,
            'business_name' => 'Lanzaloe',
            'source_platform' => 'magento',
            'source_label' => 'Lanzaloe · Magento',
        ]);
    }

    public function test_register_command_registers_all_matching_servers(): void
    {
        Server::query()->create([
            'name' => 'Lanzaloe Magento MCP',
            'slug' => 'lanzaloe-magento',
            'metadata' => [
                'business' => 'Lanzaloe',
                'source_stack' => ['magento', 'mcp'],
            ],
        ]);

        $this->artisan('external-sync:register-sources')
            ->expectsOutput('Registered 1 external source(s).')
            ->assertExitCode(0);

        $this->assertSame('Lanzaloe · Magento', ExternalSource::query()->sole()->source_label);
    }
}
