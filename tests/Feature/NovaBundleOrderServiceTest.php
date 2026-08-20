<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NovaBundleOrder;
use App\Services\Nova\NovaBundleOrderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class NovaBundleOrderServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_creates_bundle_order_and_persists_record(): void
    {
        Http::fake([
            'lageriawp.test/wp-json/wc/v3/orders' => Http::response([
                'id' => 123,
                'number' => '123',
                'status' => 'pending',
                'total' => '30.00',
                'currency' => 'EUR',
            ]),
            'lageriawp.test/wp-json/wc/v3/orders/123' => Http::response([
                'id' => 123,
                'status' => 'cancelled',
            ]),
            '*lanzaloe.com/*/V1/customers/search*' => Http::response(['items' => []]),
            '*lanzaloe.com/*/V1/customers' => Http::response([
                'id' => 20793,
                'email' => 'poc@novagestion.eu',
            ]),
            '*lanzaloe.com/*/V1/customers/20793/carts' => Http::response('41286'),
            '*lanzaloe.com/*/V1/carts/41286/items' => Http::response(['item_id' => 1, 'quote_id' => '41286']),
            '*lanzaloe.com/*/V1/carts/41286/shipping-information' => Http::response(['payment_methods' => []]),
            '*lanzaloe.com/*/V1/carts/41286/order' => Http::response('"O-456"'),
        ]);

        $service = app(NovaBundleOrderService::class);

        $result = $service->createBundle([
            'first_name' => 'Prueba',
            'last_name' => 'Nova',
            'email' => 'poc@novagestion.eu',
            'phone' => '600000000',
            'address' => 'Calle Prueba POC',
            'city' => 'Arrecife',
            'postcode' => '35500',
            'country' => 'ES',
            'street' => ['Calle Prueba POC'],
            'la_geria_product_id' => 240336,
            'la_geria_quantity' => 2,
            'lanzaloe_sku' => 'jugo_puro_250',
            'lanzaloe_quantity' => 1,
            'lanzaloe_shipping_method' => 'amstrates7',
            'lanzaloe_shipping_carrier' => 'amstrates',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(123, $result['la_geria']['order_id']);
        $this->assertSame('O-456', $result['lanzaloe']['order_id']);

        $this->assertDatabaseHas('nova_bundle_orders', [
            'bundle_reference' => $result['bundle_reference'],
            'status' => 'created',
            'la_geria_order_id' => 123,
            'lanzaloe_order_id' => 'O-456',
        ]);
    }

    public function test_marks_partial_status_when_lanzaloe_payment_fails(): void
    {
        Http::fake([
            'lageriawp.test/wp-json/wc/v3/orders' => Http::response([
                'id' => 124,
                'number' => '124',
                'status' => 'pending',
                'total' => '30.00',
                'currency' => 'EUR',
            ]),
            '*lanzaloe.com/*/V1/customers/search*' => Http::response(['items' => []]),
            '*lanzaloe.com/*/V1/customers' => Http::response([
                'id' => 20794,
                'email' => 'poc@novagestion.eu',
            ]),
            '*lanzaloe.com/*/V1/customers/20794/carts' => Http::response('41287'),
            '*lanzaloe.com/*/V1/carts/41287/items' => Http::response(['item_id' => 1, 'quote_id' => '41287']),
            '*lanzaloe.com/*/V1/carts/41287/shipping-information' => Http::response([]),
            '*lanzaloe.com/*/V1/carts/41287/order' => Http::response(['message' => 'The shipping method is missing.'], 400),
        ]);

        $service = app(NovaBundleOrderService::class);

        $result = $service->createBundle([
            'first_name' => 'Prueba',
            'last_name' => 'Nova',
            'email' => 'poc@novagestion.eu',
            'phone' => '600000000',
            'address' => 'Calle Prueba POC',
            'city' => 'Arrecife',
            'postcode' => '35500',
            'country' => 'ES',
            'street' => ['Calle Prueba POC'],
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(41287, $result['lanzaloe']['cart_id']);

        $this->assertDatabaseHas('nova_bundle_orders', [
            'bundle_reference' => $result['bundle_reference'],
            'status' => 'partial',
            'la_geria_order_id' => 124,
            'lanzaloe_cart_id' => '41287',
        ]);
    }

    public function test_uses_custom_endpoint_when_enabled(): void
    {
        config(['services.lanzaloe.use_custom_order_endpoint' => true]);

        Http::fake([
            'lageriawp.test/wp-json/wc/v3/orders' => Http::response([
                'id' => 125,
                'number' => '125',
                'status' => 'pending',
                'total' => '30.00',
                'currency' => 'EUR',
            ]),
            '*lanzaloe.com/*/V1/novagestion/create-order' => Http::response([
                'success' => true,
                'order_id' => 'O-123456',
                'order_entity_id' => 999,
                'customer_id' => 111,
                'grand_total' => 16.95,
                'message' => 'Order created successfully',
            ]),
        ]);

        $service = new NovaBundleOrderService();

        $result = $service->createBundle([
            'first_name' => 'Prueba',
            'last_name' => 'Nova',
            'email' => 'poc@novagestion.eu',
            'phone' => '600000000',
            'address' => 'Calle Prueba POC',
            'city' => 'Arrecife',
            'postcode' => '35500',
            'country' => 'ES',
            'street' => ['Calle Prueba POC'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('O-123456', $result['lanzaloe']['order_id']);
    }
}
