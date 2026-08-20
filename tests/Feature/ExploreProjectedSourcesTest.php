<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Server;
use App\Models\Tour;
use App\Models\User;
use App\Services\ExternalSync\ExternalSourceSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExploreProjectedSourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_explore_places_include_source_metadata_for_projected_models(): void
    {
        $country = Country::query()->create(['name' => 'Spain', 'code' => 'ES']);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Arrecife']);
        $location = Location::query()->create([
            'city_id' => $city->id,
            'name' => 'Arrecife',
            'latitude' => 28.963,
            'longitude' => -13.547,
        ]);
        $hotel = Hotel::query()->create([
            'name' => 'Hotel Sync',
            'location_id' => $location->id,
            'is_active' => true,
        ]);
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
        ExternalSyncMapping::query()->create([
            'server_id' => $server->id,
            'external_source_id' => $source->id,
            'business_name' => 'Taxilanz Hoteles',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'target_id' => $hotel->id,
            'external_id' => 'hotel-sync',
        ]);

        $response = $this->getJson('/explore/places')->assertOk();

        $place = collect($response->json('data'))->firstWhere('id', 'hotel-'.$hotel->id);
        $this->assertSame('Taxilanz Hoteles · MCP · Hoteles', $place['source_label']);
        $this->assertSame('Taxilanz Hoteles', $place['business_name']);
        $this->assertSame('hotel', $place['resource_type']);
    }

    public function test_latepoint_service_sync_creates_tour_visit_visible_in_explore(): void
    {
        Http::fake([
            'lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/get-services/run' => Http::response([
                'data' => [
                    'services' => [
                        [
                            'id' => 77,
                            'name' => 'Visita guiada La Geria',
                            'short_description' => 'Visita con cata',
                            'charge_amount' => '18.00',
                            'duration' => 60,
                            'status' => 'active',
                            'latitude' => 28.972,
                            'longitude' => -13.706,
                            'address' => 'La Geria',
                            'city' => 'Yaiza',
                            'country' => 'Spain',
                            'updated_at' => '2026-05-23 10:00:00',
                        ],
                    ],
                ],
            ]),
        ]);

        $server = Server::query()->create(['name' => 'La Geria', 'slug' => 'la-geria']);
        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'La Geria LatePoint Visitas',
            'business_name' => 'La Geria',
            'source_platform' => 'latepoint',
            'source_label' => 'La Geria · LatePoint · Visitas',
            'connection_type' => 'api',
            'base_url' => 'https://lageria.test',
            'api_url' => 'https://lageria.test',
            'resource_type' => 'tour_visit',
            'target_model' => 'tour',
            'capability' => 'latepoint_services',
            'status' => 'active',
        ]);

        app(ExternalSourceSynchronizer::class)->sync($source);

        $response = $this->getJson('/explore/places')->assertOk();

        $place = collect($response->json('data'))->firstWhere('name', 'Visita guiada La Geria');
        $this->assertNotNull($place);
        $this->assertSame('tour_visit', $place['type']);
        $this->assertSame('tour', $place['booking_type']);
        $this->assertSame('Visit Tour', $place['label']);
        $this->assertSame('La Geria · LatePoint · Visitas', $place['source_label']);
        $this->assertSame('La Geria', $place['business_name']);
        $this->assertSame('tour_visit', $place['resource_type']);
        $this->assertSame(1, $response->json('meta.types.tour_visit'));
        $this->assertSame(1, $response->json('meta.mappable.tour_visit'));
    }

    public function test_explore_places_classifies_tour_routes_as_taxi_routes(): void
    {
        $country = Country::query()->create(['name' => 'Spain', 'code' => 'ES']);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Tías']);
        $location = Location::query()->create([
            'city_id' => $city->id,
            'name' => 'Puerto del Carmen',
            'latitude' => 28.9249,
            'longitude' => -13.5098,
        ]);
        $user = User::query()->create([
            'name' => 'Tour Manager',
            'email' => 'tour-manager@example.com',
            'password' => 'password',
        ]);
        $tour = Tour::query()->create([
            'name' => 'Ruta aeropuerto',
            'location_id' => $location->id,
            'base_price' => 0,
            'max_capacity' => 4,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
        $server = Server::query()->create(['name' => 'Taxilanz Chauffeur', 'slug' => 'taxilanz-chauffeur']);
        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'Taxilanz Chauffeur Routes',
            'business_name' => 'Taxilanz',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz · Chauffeur · Routes',
            'connection_type' => 'api',
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'status' => 'active',
        ]);
        ExternalSyncMapping::query()->create([
            'server_id' => $server->id,
            'external_source_id' => $source->id,
            'business_name' => 'Taxilanz',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz · Chauffeur · Routes',
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'target_id' => $tour->id,
            'external_id' => 'route-airport',
        ]);

        $response = $this->getJson('/explore/places')->assertOk();

        $place = collect($response->json('data'))->firstWhere('id', 'tour-'.$tour->id);
        $this->assertNotNull($place);
        $this->assertSame('taxi_route', $place['type']);
        $this->assertSame('tour', $place['booking_type']);
        $this->assertSame('Taxi Route', $place['label']);
        $this->assertSame('tour_route', $place['resource_type']);
        $this->assertSame(1, $response->json('meta.types.taxi_route'));
        $this->assertSame(1, $response->json('meta.mappable.taxi_route'));
    }

    public function test_explore_places_include_active_restaurants_without_coordinates_for_list_view(): void
    {
        $country = Country::query()->create(['name' => 'Spain', 'code' => 'ES']);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Yaiza']);
        $location = Location::query()->create([
            'city_id' => $city->id,
            'name' => 'Pending map location',
            'latitude' => null,
            'longitude' => null,
        ]);

        $restaurant = Restaurant::query()->create([
            'restaurant_name' => 'Bodega sin coordenadas',
            'location_id' => $location->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/explore/places')->assertOk();

        $place = collect($response->json('data'))->firstWhere('id', 'restaurant-'.$restaurant->id);
        $this->assertNotNull($place);
        $this->assertSame('restaurant', $place['type']);
        $this->assertFalse($place['has_coordinates']);
        $this->assertSame(1, $response->json('meta.types.restaurant'));
        $this->assertSame(0, $response->json('meta.mappable.restaurant'));
    }
}
