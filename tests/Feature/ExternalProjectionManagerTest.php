<?php

namespace Tests\Feature;

use App\Models\ExternalCatalogItem;
use App\Models\ExternalSource;
use App\Models\Hotel;
use App\Models\Server;
use App\Services\ExternalSync\Projection\ExternalProjectionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalProjectionManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_projection_records_mapping_to_external_catalog_item(): void
    {
        $source = $this->source('wine_product', 'external_catalog_item');
        $catalogItem = ExternalCatalogItem::query()->create([
            'server_id' => $source->server_id,
            'external_source_id' => $source->id,
            'business_name' => $source->business_name,
            'source_platform' => $source->source_platform,
            'source_label' => $source->source_label,
            'external_id' => 'sku-1',
            'type' => 'product',
            'name' => 'Vino Malvasia',
            'metadata' => ['raw' => ['id' => 'sku-1']],
        ]);

        $result = app(ExternalProjectionManager::class)->project($source, $catalogItem, [
            'external_id' => 'sku-1',
            'name' => 'Vino Malvasia',
            'metadata' => ['raw' => ['id' => 'sku-1']],
        ]);

        $this->assertSame('external_catalog_item', $result->target_model);
        $this->assertSame($catalogItem->id, $result->target_id);
        $this->assertDatabaseHas('external_sync_mappings', [
            'external_source_id' => $source->id,
            'resource_type' => 'wine_product',
            'target_model' => 'external_catalog_item',
            'target_id' => $catalogItem->id,
            'external_id' => 'sku-1',
        ]);
    }

    public function test_projects_hotel_payload_to_hotel_model_idempotently(): void
    {
        $source = $this->source('hotel', 'hotel');

        $payload = [
            'external_id' => 'hotel-1',
            'name' => 'Hotel Mirador',
            'description' => 'Sea view hotel',
            'phone' => '+34 928 000 000',
            'email' => 'hotel@example.test',
            'website' => 'https://hotel.example.test',
            'metadata' => [
                'raw' => [
                    'address' => 'Arrecife',
                    'city' => 'Arrecife',
                    'country' => 'Spain',
                    'latitude' => 28.963,
                    'longitude' => -13.547,
                ],
            ],
        ];

        $first = app(ExternalProjectionManager::class)->project($source, new ExternalCatalogItem($payload), $payload);
        $secondPayload = array_replace($payload, ['name' => 'Hotel Mirador Updated']);
        $second = app(ExternalProjectionManager::class)->project($source, new ExternalCatalogItem($secondPayload), $secondPayload);

        $this->assertSame($first->target_id, $second->target_id);
        $this->assertDatabaseHas('hotels', ['id' => $first->target_id, 'name' => 'Hotel Mirador Updated']);
        $this->assertSame(1, Hotel::query()->count());
    }

    public function test_projects_restaurant_tour_and_taxi_payloads(): void
    {
        $manager = app(ExternalProjectionManager::class);

        $restaurantSource = $this->source('restaurant', 'restaurant');
        $tourSource = $this->source('tour_route', 'tour');
        $taxiSource = $this->source('taxi', 'taxi_service');

        $manager->project($restaurantSource, new ExternalCatalogItem, [
            'external_id' => 'restaurant-1',
            'name' => 'Bodega Restaurant',
            'description' => 'Local food',
            'metadata' => ['raw' => ['city' => 'Yaiza', 'country' => 'Spain', 'latitude' => 28.956, 'longitude' => -13.765]],
        ]);
        $manager->project($tourSource, new ExternalCatalogItem, [
            'external_id' => 'tour-1',
            'name' => 'Ruta Volcanes',
            'description' => 'Volcano route',
            'price' => 49.50,
            'metadata' => ['raw' => ['city' => 'Tinajo', 'country' => 'Spain', 'latitude' => 29.063, 'longitude' => -13.676]],
        ]);
        $manager->project($taxiSource, new ExternalCatalogItem, [
            'external_id' => 'taxi-1',
            'name' => 'Taxilanz Transfers',
            'description' => 'Airport transfers',
            'metadata' => ['raw' => ['city' => 'Tias', 'country' => 'Spain', 'latitude' => 28.953, 'longitude' => -13.608]],
        ]);

        $this->assertDatabaseHas('restaurants', ['restaurant_name' => 'Bodega Restaurant']);
        $this->assertDatabaseHas('tours', ['tour_name' => 'Ruta Volcanes']);
        $this->assertDatabaseHas('taxi_services', ['name' => 'Taxilanz Transfers']);
    }

    private function source(string $resourceType, string $targetModel): ExternalSource
    {
        $server = Server::query()->firstOrCreate(['slug' => 'la-geria'], ['name' => 'La Geria']);

        return ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'La Geria Woo '.$resourceType,
            'business_name' => 'La Geria',
            'source_platform' => 'woo',
            'source_label' => 'La Geria · Woo · '.$resourceType,
            'connection_type' => 'api',
            'resource_type' => $resourceType,
            'target_model' => $targetModel,
            'status' => 'active',
        ]);
    }
}
