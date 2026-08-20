<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\City;
use App\Models\Country;
use App\Models\ExternalBooking;
use App\Models\ExternalCatalogItem;
use App\Models\ExternalOrder;
use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Server;
use App\Models\TaxiService;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\TourSchedule;
use App\Models\User;
use App\Services\ExternalSync\ExternalSourceSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalSourceSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_woocommerce_products_and_orders_to_local_tables(): void
    {
        Http::fake([
            'woo.test/wp-json/wc/v3/products*' => Http::response([
                [
                    'id' => 10,
                    'name' => 'Ruta Sur',
                    'type' => 'simple',
                    'status' => 'publish',
                    'sku' => 'RUTA-SUR',
                    'price' => '140.00',
                    'regular_price' => '140.00',
                    'description' => 'Ruta por Timanfaya',
                    'permalink' => 'https://woo.test/product/ruta-sur',
                    'date_modified_gmt' => '2026-05-20T10:00:00',
                ],
            ]),
            'woo.test/wp-json/wc/v3/orders*' => Http::response([
                [
                    'id' => 55,
                    'number' => '55',
                    'status' => 'processing',
                    'payment_method' => 'redsys',
                    'total' => '140.00',
                    'currency' => 'EUR',
                    'date_created_gmt' => '2026-05-21T12:00:00',
                    'date_modified_gmt' => '2026-05-21T12:30:00',
                    'billing' => [
                        'first_name' => 'Ana',
                        'last_name' => 'López',
                        'email' => 'ana@example.com',
                        'phone' => '+34600000000',
                    ],
                    'line_items' => [
                        ['name' => 'Ruta Sur', 'quantity' => 1],
                    ],
                ],
            ]),
        ]);

        $summary = app(ExternalSourceSynchronizer::class)->sync($this->source('woo', 'Taxilanz · Woo', 'https://woo.test'));

        $this->assertSame(2, $summary['processed']);
        $this->assertDatabaseHas('external_catalog_items', [
            'source_label' => 'Taxilanz · Woo',
            'external_id' => '10',
            'type' => 'product',
            'name' => 'Ruta Sur',
            'price' => 140.00,
        ]);
        $this->assertDatabaseHas('external_orders', [
            'source_label' => 'Taxilanz · Woo',
            'external_id' => '55',
            'external_increment_id' => '55',
            'customer_name' => 'Ana López',
            'grand_total' => 140.00,
        ]);
        $this->assertSame(1, ExternalCatalogItem::query()->count());
        $this->assertSame(1, ExternalOrder::query()->count());
    }

    public function test_syncs_magento_products_and_orders_to_local_tables(): void
    {
        Http::fake([
            'magento.test/rest/V1/products*' => Http::response([
                'items' => [
                    [
                        'id' => 77,
                        'sku' => 'ALOE-GEL',
                        'name' => 'Aloe Gel',
                        'status' => 1,
                        'price' => 12.50,
                        'updated_at' => '2026-05-20 10:00:00',
                        'custom_attributes' => [
                            ['attribute_code' => 'description', 'value' => 'Gel aloe'],
                        ],
                    ],
                ],
                'total_count' => 1,
            ]),
            'magento.test/rest/V1/orders*' => Http::response([
                'items' => [
                    [
                        'entity_id' => 88,
                        'increment_id' => '000088',
                        'status' => 'complete',
                        'customer_email' => 'buyer@example.com',
                        'customer_firstname' => 'Luis',
                        'customer_lastname' => 'Pérez',
                        'grand_total' => 12.50,
                        'order_currency_code' => 'EUR',
                        'created_at' => '2026-05-21 12:00:00',
                        'updated_at' => '2026-05-21 12:30:00',
                        'items' => [['sku' => 'ALOE-GEL']],
                    ],
                ],
                'total_count' => 1,
            ]),
        ]);

        $summary = app(ExternalSourceSynchronizer::class)->sync($this->source('magento', 'Lanzaloe · Magento', 'https://magento.test'));

        $this->assertSame(2, $summary['processed']);
        $this->assertDatabaseHas('external_catalog_items', [
            'source_label' => 'Lanzaloe · Magento',
            'external_id' => '77',
            'name' => 'Aloe Gel',
            'sku' => 'ALOE-GEL',
        ]);
        $this->assertDatabaseHas('external_orders', [
            'source_label' => 'Lanzaloe · Magento',
            'external_id' => '88',
            'external_increment_id' => '000088',
            'customer_name' => 'Luis Pérez',
        ]);
    }

    public function test_syncs_latepoint_bookings_to_local_table(): void
    {
        Http::fake([
            'lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run' => Http::response([
                'data' => [
                    [
                        'id' => 501,
                        'status' => 'approved',
                        'customer_name' => 'Marta',
                        'customer_email' => 'marta@example.com',
                        'customer_phone' => '+34611111111',
                        'service_name' => 'Visita guiada',
                        'start_datetime' => '2026-05-24 10:00:00',
                        'end_datetime' => '2026-05-24 11:00:00',
                    ],
                ],
            ]),
        ]);

        $summary = app(ExternalSourceSynchronizer::class)->sync($this->source('latepoint', 'La Geria · LatePoint', 'https://lageria.test'));

        $this->assertSame(1, $summary['processed']);
        $this->assertDatabaseHas('external_bookings', [
            'source_label' => 'La Geria · LatePoint',
            'external_id' => '501',
            'booking_type' => 'latepoint',
            'customer_name' => 'Marta',
            'service_name' => 'Visita guiada',
        ]);
        $this->assertSame(1, ExternalBooking::query()->count());
    }

    public function test_latepoint_bookings_project_to_tour_bookings_when_target_is_tour_booking(): void
    {
        Artisan::call('migrate', ['--path' => 'database/migrations/Booking']);

        Http::fake([
            'lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run' => Http::response([
                'data' => [
                    [
                        'id' => 701,
                        'service_id' => 99,
                        'status' => 'approved',
                        'payment_status' => 'paid',
                        'customer_name' => 'Marta',
                        'customer_email' => 'marta@example.com',
                        'customer_phone' => '+34611111111',
                        'service_name' => 'Visita guiada',
                        'participants' => 2,
                        'start_datetime' => '2026-05-24 10:00:00',
                        'end_datetime' => '2026-05-24 11:00:00',
                    ],
                ],
            ]),
        ]);

        $user = User::query()->create([
            'name' => 'Imported Guest',
            'email' => 'imported@example.test',
            'password' => 'password',
        ]);

        $country = Country::query()->create(['code' => 'ES', 'name' => 'Spain']);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Yaiza']);
        $location = Location::query()->create([
            'city_id' => $city->id,
            'name' => 'La Geria',
            'latitude' => 28.972,
            'longitude' => -13.706,
        ]);

        $tour = Tour::query()->create([
            'tour_name' => 'Visita La Geria',
            'location_id' => $location->id,
            'base_price' => 15,
            'max_capacity' => 10,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $servicesSource = $this->source('latepoint', 'La Geria · LatePoint · Visitas', 'https://lageria.test');
        $servicesSource->forceFill(['resource_type' => 'tour_visit', 'target_model' => 'tour'])->save();

        ExternalSyncMapping::query()->create([
            'server_id' => $servicesSource->server_id,
            'external_source_id' => $servicesSource->id,
            'business_name' => 'La Geria',
            'source_platform' => 'latepoint',
            'source_label' => 'La Geria · LatePoint · Visitas',
            'resource_type' => 'tour_visit',
            'target_model' => 'tour',
            'target_id' => $tour->id,
            'external_id' => '99',
        ]);

        $bookingsSource = $this->source('latepoint', 'La Geria · LatePoint · Reservas visitas', 'https://lageria.test');
        $bookingsSource->forceFill(['resource_type' => 'tour_booking', 'target_model' => 'tour_booking'])->save();

        $summary = app(ExternalSourceSynchronizer::class)->sync($bookingsSource);

        $this->assertSame(1, $summary['processed']);
        $this->assertSame(1, ExternalBooking::query()->count());
        $this->assertSame(1, Booking::query()->count());
        $this->assertSame(1, TourSchedule::query()->count());
        $this->assertSame(1, TourBooking::query()->count());

        $this->assertDatabaseHas('tour_bookings', [
            'tour_id' => $tour->id,
            'number_of_adults' => 2,
            'number_of_children' => 0,
        ]);
    }

    public function test_syncs_sirvo_branches_to_restaurant_resource(): void
    {
        Http::fake([
            'sirvo.test/api/branches*' => Http::response([
                'data' => [
                    [
                        'id' => 7,
                        'name' => 'Sirvo Playa Blanca',
                        'description' => 'Restaurante frente al mar',
                        'phone' => '+34928000000',
                        'email' => 'hola@sirvo.test',
                        'address' => 'Avenida Maritima 1',
                        'city' => 'Playa Blanca',
                        'latitude' => 28.864,
                        'longitude' => -13.829,
                    ],
                ],
            ]),
        ]);

        $source = $this->source('sirvo', 'Sirvo · Restaurantes', 'https://sirvo.test');
        $source->forceFill([
            'resource_type' => 'restaurant',
            'target_model' => 'restaurant',
            'capability' => 'branches',
        ])->save();

        $summary = app(ExternalSourceSynchronizer::class)->sync($source);

        $this->assertSame(1, $summary['processed']);
        $this->assertDatabaseHas('external_catalog_items', [
            'source_label' => 'Sirvo · Restaurantes',
            'external_id' => '7',
            'type' => 'restaurant',
            'name' => 'Sirvo Playa Blanca',
        ]);
        $this->assertDatabaseHas('restaurants', [
            'restaurant_name' => 'Sirvo Playa Blanca',
            'phone' => '+34928000000',
            'email' => 'hola@sirvo.test',
        ]);
        $this->assertDatabaseHas('external_sync_mappings', [
            'source_label' => 'Sirvo · Restaurantes',
            'resource_type' => 'restaurant',
            'target_model' => 'restaurant',
            'external_id' => '7',
        ]);
        $this->assertSame(1, Restaurant::query()->count());
    }

    public function test_sirvo_sync_logs_in_when_bearer_token_is_not_configured(): void
    {
        putenv('TEST_SIRVO_USER=admin@example.com');
        putenv('TEST_SIRVO_PASSWORD=secret-password');

        Http::fake([
            'sirvo-auth.test/api/auth/login' => Http::response([
                'session' => [
                    'access_token' => 'session-token',
                    'token_type' => 'bearer',
                ],
            ]),
            'sirvo-auth.test/api/branches*' => Http::response([
                'data' => [
                    ['id' => 9, 'name' => 'Sirvo Arrecife'],
                ],
            ]),
        ]);

        $server = Server::query()->create([
            'name' => 'Sirvo Auth',
            'slug' => 'sirvo-auth',
            'metadata' => [
                'login' => [
                    'path' => '/api/auth/login',
                    'user_env' => 'TEST_SIRVO_USER',
                    'password_env' => 'TEST_SIRVO_PASSWORD',
                ],
            ],
        ]);

        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'Sirvo Auth · Restaurantes',
            'business_name' => 'Sirvo',
            'source_platform' => 'sirvo',
            'source_label' => 'Sirvo Auth · Restaurantes',
            'connection_type' => 'api',
            'base_url' => 'https://sirvo-auth.test',
            'api_url' => 'https://sirvo-auth.test',
            'resource_type' => 'restaurant',
            'target_model' => 'restaurant',
            'capability' => 'branches',
            'status' => 'active',
        ]);

        app(ExternalSourceSynchronizer::class)->sync($source);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sirvo-auth.test/api/branches'
            && $request->hasHeader('Authorization', 'Bearer session-token'));
    }

    public function test_syncs_chauffeur_bookings_as_taxi_services_when_source_targets_taxi_service(): void
    {
        Http::fake([
            'taxilanz.test/wp-json/taxilanz-mcp/v1/chauffeur/bookings*' => Http::response([
                'data' => [
                    [
                        'id' => 226256,
                        'service_name' => 'Transfer aeropuerto',
                        'pickup_location' => 'Aeropuerto de Lanzarote',
                        'dropoff_location' => 'Puerto del Carmen',
                        'customer_phone' => '+34622222222',
                        'pickup_datetime' => '2026-05-24 12:00:00',
                    ],
                ],
            ]),
        ]);

        $source = $this->source('woo', 'Taxilanz · Chauffeur · Taxis', 'https://taxilanz.test');
        $source->forceFill([
            'resource_type' => 'taxi',
            'target_model' => 'taxi_service',
            'capability' => 'chauffeur_bookings',
        ])->save();

        $summary = app(ExternalSourceSynchronizer::class)->sync($source);

        $this->assertSame(1, $summary['processed']);
        $this->assertDatabaseHas('external_catalog_items', [
            'source_label' => 'Taxilanz · Chauffeur · Taxis',
            'external_id' => '226256',
            'type' => 'taxi',
            'name' => 'Transfer aeropuerto',
        ]);
        $this->assertDatabaseHas('taxi_services', [
            'name' => 'Transfer aeropuerto',
            'phone' => '+34622222222',
        ]);
        $this->assertSame(1, TaxiService::query()->count());
    }

    public function test_syncs_chauffeur_routes_as_tour_routes(): void
    {
        Http::fake([
            'taxilanz.test/wp-json/taxilanz-mcp/v1/chauffeur/routes*' => Http::response([
                'routes' => [
                    [
                        'id' => 301,
                        'post_type' => 'chbs_route',
                        'title' => 'Ruta Timanfaya Privada',
                        'description' => 'Ruta Chauffeur por Timanfaya',
                        'short_description' => 'Tour route',
                        'status' => 'publish',
                        'price' => '120.00',
                        'duration_hours' => 4,
                        'modified_gmt' => '2026-05-20 10:00:00',
                    ],
                ],
            ]),
        ]);

        $source = $this->source('woo', 'Taxilanz · Chauffeur · Rutas', 'https://taxilanz.test');
        $source->forceFill([
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'capability' => 'chauffeur_routes',
        ])->save();

        $summary = app(ExternalSourceSynchronizer::class)->sync($source);

        $this->assertSame(1, $summary['processed']);
        $this->assertDatabaseHas('external_catalog_items', [
            'source_label' => 'Taxilanz · Chauffeur · Rutas',
            'external_id' => '301',
            'type' => 'tour',
            'name' => 'Ruta Timanfaya Privada',
            'price' => 120.00,
        ]);
        $this->assertDatabaseHas('tours', [
            'tour_name' => 'Ruta Timanfaya Privada',
            'base_price' => 120.00,
            'duration_hours' => 4,
        ]);
        $this->assertDatabaseHas('external_sync_mappings', [
            'external_source_id' => $source->id,
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'external_id' => '301',
        ]);
    }

    public function test_woocommerce_route_source_projects_products_to_tours_without_projecting_orders_as_tours(): void
    {
        Http::fake([
            'woo-routes.test/wp-json/wc/v3/products*' => Http::response([
                [
                    'id' => 10,
                    'name' => 'Ruta Sur',
                    'type' => 'simple',
                    'status' => 'publish',
                    'sku' => 'RUTA-SUR',
                    'price' => '140.00',
                    'regular_price' => '140.00',
                    'description' => 'Ruta por Timanfaya',
                    'date_modified_gmt' => '2026-05-20T10:00:00',
                ],
            ]),
            'woo-routes.test/wp-json/wc/v3/orders*' => Http::response([
                [
                    'id' => 55,
                    'number' => '55',
                    'status' => 'processing',
                    'total' => '140.00',
                    'currency' => 'EUR',
                    'billing' => ['first_name' => 'Ana', 'last_name' => 'López'],
                    'line_items' => [['name' => 'Ruta Sur', 'quantity' => 1]],
                ],
            ]),
        ]);

        $source = $this->source('woo', 'Taxilanz · Woo · Rutas', 'https://woo-routes.test');
        $source->forceFill([
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'capability' => 'routes',
        ])->save();

        $summary = app(ExternalSourceSynchronizer::class)->sync($source);

        $this->assertSame(1, $summary['processed']);
        $this->assertDatabaseHas('tours', [
            'tour_name' => 'Ruta Sur',
            'base_price' => 140.00,
        ]);
        $this->assertSame(1, Tour::query()->count());
        $this->assertSame(0, ExternalOrder::query()->count());
    }

    public function test_latepoint_sync_uses_server_metadata_auth_headers(): void
    {
        putenv('TEST_LATEPOINT_BEARER=metadata-token');
        putenv('TEST_LATEPOINT_LOCAL=local-token');

        Http::fake([
            'secure-lageria.test/wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run' => Http::response([
                'data' => [],
            ]),
        ]);

        $server = Server::query()->create([
            'name' => 'La Geria Secure',
            'slug' => 'la-geria-secure',
            'metadata' => [
                'auth_token_env' => 'TEST_LATEPOINT_BEARER',
                'local_header' => [
                    'name' => 'X-MCP-Studio-Token',
                    'env' => 'TEST_LATEPOINT_LOCAL',
                ],
            ],
        ]);

        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'La Geria LatePoint',
            'business_name' => 'La Geria',
            'source_platform' => 'latepoint',
            'source_label' => 'La Geria · LatePoint',
            'connection_type' => 'api',
            'base_url' => 'https://secure-lageria.test',
            'api_url' => 'https://secure-lageria.test',
            'status' => 'active',
        ]);

        app(ExternalSourceSynchronizer::class)->sync($source);

        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer metadata-token')
            && $request->hasHeader('X-MCP-Studio-Token', 'local-token'));
    }

    private function source(string $platform, string $label, string $baseUrl): ExternalSource
    {
        $server = Server::query()->create([
            'name' => $label,
            'slug' => str($label)->slug()->toString(),
        ]);

        return ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => $label,
            'business_name' => str($label)->before(' ·')->toString(),
            'source_platform' => $platform,
            'source_label' => $label,
            'connection_type' => 'api',
            'base_url' => $baseUrl,
            'api_url' => $baseUrl,
            'credentials' => ['access_token' => 'secret', 'consumer_key' => 'ck', 'consumer_secret' => 'cs'],
            'status' => 'active',
        ]);
    }
}
