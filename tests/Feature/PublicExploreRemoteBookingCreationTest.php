<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use App\Models\Location;
use App\Models\PublicBookingRequest;
use App\Models\Restaurant;
use App\Models\Server;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicExploreRemoteBookingCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_restaurant_request_creates_sirvo_reservation_when_source_is_mapped(): void
    {
        Http::fake([
            'sirvo.test/api/reservations' => Http::response([
                'id' => 'sirvo-reservation-123',
                'short_code' => 'SV123',
            ], 201),
        ]);

        $restaurant = $this->restaurantWithLocation();
        $source = $this->source([
            'source_platform' => 'sirvo',
            'resource_type' => 'restaurant',
            'target_model' => 'restaurant',
            'base_url' => 'https://sirvo.test',
            'api_url' => 'https://sirvo.test',
        ]);

        ExternalSyncMapping::query()->create([
            'server_id' => $source->server_id,
            'external_source_id' => $source->id,
            'business_name' => 'Sirvo',
            'source_platform' => 'sirvo',
            'source_label' => 'Sirvo · Reservas · Restaurantes',
            'resource_type' => 'restaurant',
            'target_model' => 'restaurant',
            'target_id' => $restaurant->id,
            'external_id' => 'sirvo-restaurant-uuid',
        ]);

        $response = $this->postJson('/explore/requests', [
            'type' => 'restaurant',
            'service_id' => $restaurant->id,
            'customer_name' => 'Cliente Sirvo',
            'customer_phone' => '+34600111222',
            'customer_email' => 'sirvo@example.test',
            'guests' => 4,
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '20:30',
            'notes' => 'Mesa exterior',
        ])->assertCreated();

        $response->assertJsonPath('data.remote_booking.status', 'created');
        $response->assertJsonPath('data.remote_booking.external_id', 'sirvo-reservation-123');

        $request = PublicBookingRequest::query()->firstOrFail();
        $this->assertSame('created', $request->remote_booking_status);
        $this->assertSame('sirvo-reservation-123', $request->remote_external_id);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sirvo.test/api/reservations'
            && $request['restaurantId'] === 'sirvo-restaurant-uuid'
            && $request['name'] === 'Cliente Sirvo'
            && $request['booking_time'] === '20:30'
        );
    }

    public function test_front_tour_visit_request_creates_latepoint_booking_when_source_is_mapped(): void
    {
        Http::fake([
            'lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/get-customer-by-email/run' => Http::response([
                'data' => [
                    'id' => 321,
                ],
            ], 200),
            'lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/create-booking/run' => Http::response([
                'data' => [
                    'id' => 987,
                ],
            ], 200),
        ]);

        $tour = $this->tourWithLocation();
        $source = $this->source([
            'source_platform' => 'latepoint',
            'resource_type' => 'tour_visit',
            'target_model' => 'tour',
            'base_url' => 'https://lageria.test',
            'api_url' => 'https://lageria.test',
        ]);

        ExternalSyncMapping::query()->create([
            'server_id' => $source->server_id,
            'external_source_id' => $source->id,
            'business_name' => 'La Geria',
            'source_platform' => 'latepoint',
            'source_label' => 'La Geria · LatePoint · Visitas',
            'resource_type' => 'tour_visit',
            'target_model' => 'tour',
            'target_id' => $tour->id,
            'external_id' => '77',
        ]);

        $response = $this->postJson('/explore/requests', [
            'type' => 'tour_visit',
            'service_id' => $tour->id,
            'customer_name' => 'Cliente LatePoint',
            'customer_phone' => '+34600333444',
            'customer_email' => 'latepoint@example.test',
            'adults' => 2,
            'children' => 0,
            'tour_date' => now()->addDay()->toDateString(),
            'tour_schedule' => '11:30',
            'notes' => 'Sin alcohol',
        ])->assertCreated();

        $response->assertJsonPath('data.remote_booking.status', 'created');
        $response->assertJsonPath('data.remote_booking.external_id', '987');

        $request = PublicBookingRequest::query()->firstOrFail();
        $this->assertSame('created', $request->remote_booking_status);
        $this->assertSame('987', $request->remote_external_id);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/create-booking/run'
            && (int) data_get($request->data(), 'input.customer_id') === 321
            && (int) data_get($request->data(), 'input.service_id') === 77
            && data_get($request->data(), 'input.start_date') === now()->addDay()->toDateString()
            && data_get($request->data(), 'input.start_time') === 690
        );
    }

    public function test_front_taxi_route_request_creates_woo_checkout_payment(): void
    {
        Http::fake([
            'taxilanz.test/wp-json/taxilanz-mcp/v1/chauffeur/route-checkout' => Http::response([
                'checkout_url' => 'https://taxilanz.test/checkout/order-pay/123',
            ], 200),
        ]);

        $tour = $this->tourWithLocation();
        $source = $this->source([
            'source_platform' => 'woo',
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'base_url' => 'https://taxilanz.test',
            'api_url' => 'https://taxilanz.test',
        ]);

        ExternalSyncMapping::query()->create([
            'server_id' => $source->server_id,
            'external_source_id' => $source->id,
            'business_name' => 'Taxilanz',
            'source_platform' => 'woo',
            'source_label' => 'Taxilanz · Woo · Rutas',
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'target_id' => $tour->id,
            'external_id' => '555',
        ]);

        $response = $this->postJson('/explore/requests', [
            'type' => 'taxi_route',
            'service_id' => $tour->id,
            'customer_name' => 'Cliente Ruta',
            'customer_phone' => '+34600666777',
            'customer_email' => 'ruta@example.test',
            'adults' => 2,
            'children' => 0,
            'passengers' => 2,
            'tour_date' => now()->addDay()->toDateString(),
            'tour_schedule' => '12:00',
            'pickup_address' => 'Hotel Princesa Yaiza',
            'base_price' => 280,
        ])->assertCreated();

        $response->assertJsonPath('data.remote_booking.status', 'created');
        $response->assertJsonPath('data.payment.amount_cents', 28000);

        $request = PublicBookingRequest::query()->firstOrFail();
        $this->assertSame('transfer', $request->type);
        $this->assertSame('taxi_route', $request->booking_kind);
        $this->assertSame('pending', $request->payment_status);
        $this->assertSame(28000, $request->payment_amount_cents);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://taxilanz.test/wp-json/taxilanz-mcp/v1/chauffeur/route-checkout'
            && $request['origin'] === 'Hotel Princesa Yaiza'
            && $request['pickup_time'] === '12:00'
            && (int) $request['passengers'] === 2
        );
    }

    private function restaurantWithLocation(): Restaurant
    {
        $location = $this->location();

        return Restaurant::query()->create([
            'restaurant_name' => 'Sirvo Demo',
            'location_id' => $location->id,
            'has_reservation' => true,
            'is_active' => true,
        ]);
    }

    private function tourWithLocation(): Tour
    {
        $location = $this->location();
        $user = User::query()->create([
            'name' => 'Tour Manager',
            'email' => 'tour-manager@example.test',
            'password' => 'password',
        ]);

        return Tour::query()->create([
            'name' => 'Visita Demo',
            'location_id' => $location->id,
            'base_price' => 18,
            'max_capacity' => 12,
            'created_by' => $user->id,
            'is_active' => true,
        ]);
    }

    private function location(): Location
    {
        $country = Country::query()->firstOrCreate(['code' => 'ES'], ['name' => 'Spain']);
        $city = City::query()->firstOrCreate(['country_id' => $country->id, 'name' => 'Yaiza']);

        return Location::query()->create([
            'city_id' => $city->id,
            'name' => 'La Geria',
            'latitude' => 28.972,
            'longitude' => -13.706,
        ]);
    }

    private function source(array $attributes): ExternalSource
    {
        $server = Server::query()->create([
            'name' => $attributes['source_platform'].' server',
            'slug' => $attributes['source_platform'].'-server',
        ]);

        return ExternalSource::query()->create($attributes + [
            'server_id' => $server->id,
            'name' => $attributes['source_platform'].' source',
            'business_name' => $attributes['source_platform'],
            'source_label' => $attributes['source_platform'].' source',
            'connection_type' => 'api',
            'status' => 'active',
        ]);
    }
}
