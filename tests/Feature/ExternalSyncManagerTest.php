<?php

namespace Tests\Feature;

use App\Models\ExternalCatalogItem;
use App\Models\ExternalSource;
use App\Models\ExternalSyncLog;
use App\Models\Server;
use App\Services\ExternalSync\ExternalSyncManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ExternalSyncManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_upserts_catalog_item_with_source_identity(): void
    {
        $source = $this->source();
        $manager = app(ExternalSyncManager::class);

        $item = $manager->upsertCatalogItem($source, [
            'external_id' => '123',
            'type' => 'product',
            'status' => 'publish',
            'name' => 'Vino Malvasia',
            'price' => 18.00,
            'currency' => 'EUR',
            'metadata' => ['raw' => ['id' => 123]],
        ]);

        $updated = $manager->upsertCatalogItem($source, [
            'external_id' => '123',
            'type' => 'product',
            'status' => 'publish',
            'name' => 'Vino Malvasia Volcanica',
            'price' => 20.00,
            'currency' => 'EUR',
            'metadata' => ['raw' => ['id' => 123]],
        ]);

        $this->assertTrue($item->is($updated));
        $this->assertSame(1, ExternalCatalogItem::query()->count());
        $this->assertSame('La Geria · Woo', $updated->source_label);
        $this->assertSame('La Geria', $updated->business_name);
        $this->assertSame('woo', $updated->source_platform);
        $this->assertSame('Vino Malvasia Volcanica', $updated->name);
    }

    public function test_records_failed_sync_state_and_log(): void
    {
        $source = $this->source();
        $manager = app(ExternalSyncManager::class);

        $this->expectException(RuntimeException::class);

        try {
            $manager->run($source, 'external-sync:test', 'catalog', function (): void {
                throw new RuntimeException('Remote unavailable');
            });
        } finally {
            $source->refresh();

            $this->assertNotNull($source->last_sync_started_at);
            $this->assertNotNull($source->last_sync_failed_at);
            $this->assertSame('Remote unavailable', $source->last_sync_error);
            $this->assertDatabaseHas('external_sync_logs', [
                'external_source_id' => $source->id,
                'server_id' => $source->server_id,
                'command' => 'external-sync:test',
                'sync_type' => 'catalog',
                'status' => 'failed',
                'error' => 'Remote unavailable',
            ]);
            $this->assertSame(1, ExternalSyncLog::query()->count());
        }
    }

    public function test_catalog_upsert_projects_to_native_model_when_source_has_target_model(): void
    {
        $source = $this->source();
        $source->forceFill([
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'business_name' => 'Taxilanz Hoteles',
        ])->save();

        $manager = app(ExternalSyncManager::class);
        $manager->upsertCatalogItem($source, [
            'external_id' => 'hotel-123',
            'type' => 'hotel',
            'name' => 'Hotel Sync',
            'description' => 'Projected hotel',
            'metadata' => [
                'raw' => [
                    'city' => 'Arrecife',
                    'country' => 'Spain',
                    'latitude' => 28.963,
                    'longitude' => -13.547,
                ],
            ],
        ]);

        $this->assertDatabaseHas('hotels', ['name' => 'Hotel Sync']);
        $this->assertDatabaseHas('external_sync_mappings', [
            'external_source_id' => $source->id,
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'external_id' => 'hotel-123',
        ]);
    }

    private function source(): ExternalSource
    {
        $server = Server::query()->create([
            'name' => 'La Geria WordPress Woo LatePoint MCP',
            'slug' => 'la-geria-wordpress-woo-latepoint',
        ]);

        return ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'La Geria Woo',
            'business_name' => 'La Geria',
            'source_platform' => 'woo',
            'source_label' => 'La Geria · Woo',
            'connection_type' => 'api',
            'status' => 'active',
        ]);
    }
}
