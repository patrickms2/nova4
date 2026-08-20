<?php

namespace Tests\Feature;

use App\Models\ExternalCatalogItem;
use App\Models\ExternalSource;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExternalSyncPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_sync_tables_are_created(): void
    {
        $this->assertTrue(Schema::hasTable('external_sources'));
        $this->assertTrue(Schema::hasTable('external_catalog_items'));
        $this->assertTrue(Schema::hasTable('external_bookings'));
        $this->assertTrue(Schema::hasTable('external_orders'));
        $this->assertTrue(Schema::hasTable('external_sync_logs'));
    }

    public function test_catalog_items_preserve_server_and_source_identity(): void
    {
        $server = Server::query()->create([
            'name' => 'Lanzaloe Magento MCP',
            'slug' => 'lanzaloe-magento',
            'description' => 'Magento server',
            'metadata' => [
                'business' => 'Lanzaloe',
                'source_stack' => ['magento', 'mcp'],
            ],
        ]);

        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'Lanzaloe Magento API',
            'business_name' => 'Lanzaloe',
            'source_platform' => 'magento',
            'source_label' => 'Lanzaloe · Magento',
            'connection_type' => 'api',
            'status' => 'active',
        ]);

        $item = ExternalCatalogItem::query()->create([
            'server_id' => $server->id,
            'external_source_id' => $source->id,
            'business_name' => 'Lanzaloe',
            'source_platform' => 'magento',
            'source_label' => 'Lanzaloe · Magento',
            'external_id' => 'sku-123',
            'type' => 'product',
            'status' => 'enabled',
            'name' => 'Aloe Vera Gel',
            'sku' => 'ALOE-GEL',
            'price' => 14.50,
            'currency' => 'EUR',
            'metadata' => ['raw' => ['sku' => 'ALOE-GEL']],
        ]);

        $this->assertTrue($server->externalSources()->first()->is($source));
        $this->assertTrue($source->catalogItems()->first()->is($item));
        $this->assertSame('Lanzaloe · Magento', $item->source_label);
        $this->assertSame('Lanzaloe', $item->business_name);
        $this->assertSame('magento', $item->source_platform);
        $this->assertSame(['raw' => ['sku' => 'ALOE-GEL']], $item->metadata);
    }
}
