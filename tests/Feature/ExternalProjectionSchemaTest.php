<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalProjectionSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_sources_store_projection_classification(): void
    {
        $server = Server::query()->create(['name' => 'Taxilanz Hoteles', 'slug' => 'taxilanz-hoteles']);

        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'Taxilanz Hoteles MCP',
            'business_name' => 'Taxilanz Hoteles',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'connection_type' => 'api',
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'sync_direction' => 'remote_to_local',
            'capability' => 'hotels',
            'status' => 'active',
        ]);

        $this->assertSame('hotel', $source->resource_type);
        $this->assertSame('hotel', $source->target_model);
        $this->assertSame('remote_to_local', $source->sync_direction);
        $this->assertSame('hotels', $source->capability);
    }

    public function test_external_sync_mapping_links_remote_record_to_local_model(): void
    {
        $server = Server::query()->create(['name' => 'Taxilanz Hoteles', 'slug' => 'taxilanz-hoteles']);
        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'Taxilanz Hoteles MCP',
            'business_name' => 'Taxilanz Hoteles',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'connection_type' => 'api',
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'status' => 'active',
        ]);
        $hotel = Hotel::query()->create([
            'name' => 'Hotel Volcan',
            'location_id' => $this->locationId(),
            'is_active' => true,
        ]);

        $mapping = ExternalSyncMapping::query()->create([
            'server_id' => $server->id,
            'external_source_id' => $source->id,
            'business_name' => 'Taxilanz Hoteles',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'target_id' => $hotel->id,
            'external_id' => 'hotel-123',
            'payload_hash' => sha1('hotel-123'),
            'last_synced_at' => now(),
        ]);

        $this->assertTrue($mapping->source->is($source));
        $this->assertSame('Taxilanz Hoteles · MCP · Hoteles', $hotel->externalSyncMappings()->first()->source_label);
    }

    private function locationId(): int
    {
        $countryId = Country::query()->create(['name' => 'Spain', 'code' => 'ES'])->id;
        $cityId = City::query()->create(['country_id' => $countryId, 'name' => 'Arrecife'])->id;

        return Location::query()->create([
            'city_id' => $cityId,
            'name' => 'Arrecife',
        ])->id;
    }
}
