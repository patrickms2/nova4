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

class PublicExploreTourBookingAvailabilityEnforcedTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_booking_request_rejects_unavailable_tour_slot(): void
    {
        putenv('TEST_LATEPOINT_BEARER=latepoint-token');
        putenv('TEST_LATEPOINT_LOCAL=local-token');

        Http::fake([
            'lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run' => Http::response([
                'data' => [
                    [
                        'id' => 501,
                        'service_id' => 77,
                        'participants' => 1,
                        'start_datetime' => '2026-05-24 11:00:00',
                    ],
                ],
            ]),
            'lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/get-customer-by-email/run' => Http::response([
                'data' => ['id' => 321],
            ], 200),
            'lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/create-booking/run' => Http::response([
                'data' => ['booking' => ['id' => 999]],
            ], 200),
        ]);

        $tour = $this->tourWithLatePointMapping('77');

        $this->postJson('/explore/requests', [
            'type' => 'tour_visit',
            'service_id' => $tour->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '+34600000000',
            'adults' => 1,
            'children' => 0,
            'tour_date' => '2026-05-24',
            'tour_schedule' => '11:00',
        ])->assertStatus(422);
    }

    private function tourWithLatePointMapping(string $externalServiceId): Tour
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
