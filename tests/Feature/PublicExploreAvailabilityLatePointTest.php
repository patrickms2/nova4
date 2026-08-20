<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use App\Models\Location;
use App\Models\Server;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicExploreAvailabilityLatePointTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_marks_remote_latepoint_booked_slots_as_unavailable(): void
    {
        putenv('TEST_LATEPOINT_BEARER=latepoint-token');
        putenv('TEST_LATEPOINT_LOCAL=local-token');

        Http::fake([
            'lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run' => Http::response([
                'data' => [
                    [
                        'id' => 501,
                        'service_id' => 77,
                        'start_datetime' => '2026-05-24 10:00:00',
                    ],
                ],
            ]),
        ]);

        $tour = $this->tourWithMapping('77');

        $response = $this->getJson('/explore/availability?type=tour_visit&service_id='.$tour->id.'&date=2026-05-24&participants=1')
            ->assertOk();

        $response->assertJsonPath('data.source.source_label', 'La Geria · LatePoint · Visitas');
        $response->assertJsonPath('data.times.0.time', '10:00');
        $response->assertJsonPath('data.times.0.available', false);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run'
            && $request->hasHeader('Authorization', 'Bearer latepoint-token')
            && $request->hasHeader('X-MCP-Studio-Token', 'local-token')
        );
    }

    private function tourWithMapping(string $externalServiceId): Tour
    {
        $country = Country::query()->firstOrCreate(['code' => 'ES'], ['name' => 'Spain']);
        $city = City::query()->firstOrCreate(['country_id' => $country->id, 'name' => 'Yaiza']);
        $location = Location::query()->create([
            'city_id' => $city->id,
            'name' => 'La Geria',
            'latitude' => 28.972,
            'longitude' => -13.706,
        ]);

        $user = User::query()->create([
            'name' => 'Tour Manager',
            'email' => 'tour-manager@example.test',
            'password' => 'password',
        ]);

        $server = Server::query()->create([
            'name' => 'La Geria WordPress',
            'slug' => 'la-geria-wordpress-woo-latepoint',
            'metadata' => [
                'remote_endpoint' => 'https://lageria.test',
                'auth_token_env' => 'TEST_LATEPOINT_BEARER',
                'local_header' => [
                    'name' => 'X-MCP-Studio-Token',
                    'env' => 'TEST_LATEPOINT_LOCAL',
                ],
                'capabilities' => [
                    'latepoint_bookings' => '/wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run',
                ],
            ],
        ]);

        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'La Geria LatePoint',
            'business_name' => 'La Geria',
            'source_platform' => 'latepoint',
            'source_label' => 'La Geria · LatePoint · Visitas',
            'resource_type' => 'tour_visit',
            'target_model' => 'tour',
            'connection_type' => 'api',
            'base_url' => 'https://lageria.test',
            'api_url' => 'https://lageria.test',
            'status' => 'active',
        ]);

        $tour = Tour::query()->create([
            'name' => 'Visita Guiada ES',
            'location_id' => $location->id,
            'base_price' => 18,
            'max_capacity' => 1,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        ExternalSyncMapping::query()->create([
            'server_id' => $server->id,
            'external_source_id' => $source->id,
            'business_name' => 'La Geria',
            'source_platform' => 'latepoint',
            'source_label' => $source->source_label,
            'resource_type' => 'tour_visit',
            'target_model' => 'tour',
            'target_id' => $tour->id,
            'external_id' => $externalServiceId,
        ]);

        return $tour;
    }
}
