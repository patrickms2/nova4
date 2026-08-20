<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaBundleOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class NovaBundleOrderService
{
    private string $laGeriaBaseUrl;

    private string $laGeriaClient;

    private string $laGeriaSecret;

    private string $lanzaloeBaseUrl;

    private string $lanzaloeToken;

    private bool $useCustomOrderEndpoint;

    public function __construct()
    {
        $this->laGeriaBaseUrl = rtrim((string) env('LAGERIA_ENDPOINT_URL', 'https://lageriawp.test'), '/');
        $this->laGeriaClient = (string) env('LAGERIA_WOO_REST_API_CLIENT');
        $this->laGeriaSecret = (string) env('LAGERIA_WOO_REST_API_CLIENT_SECRET');
        $this->lanzaloeBaseUrl = rtrim((string) config('services.lanzaloe.base_url', 'https://www.lanzaloe.com'), '/');
        $this->lanzaloeToken = (string) config('services.lanzaloe.api_token');
        $this->useCustomOrderEndpoint = (bool) config('services.lanzaloe.use_custom_order_endpoint', false);
    }

    /**
     * Create a cross-platform bundle order: La Geria visit + Lanzaloe product.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createBundle(array $data): array
    {
        $customer = $this->normalizeCustomer($data);
        $laGeriaItem = [
            'product_id' => $data['la_geria_product_id'] ?? 240336,
            'quantity' => $data['la_geria_quantity'] ?? 2,
        ];
        $lanzaloeItem = [
            'sku' => $data['lanzaloe_sku'] ?? 'jugo_puro_250',
            'quantity' => $data['lanzaloe_quantity'] ?? 1,
        ];

        $laGeriaResult = $this->createLaGeriaOrder($customer, $laGeriaItem, $data['la_geria_status'] ?? 'pending');
        $lanzaloeResult = $this->createLanzaloeOrder($customer, $lanzaloeItem, $data);

        $success = $laGeriaResult['success'] && $lanzaloeResult['success'];
        $bundleReference = $this->generateBundleReference($laGeriaResult, $lanzaloeResult);

        $record = NovaBundleOrder::query()->create([
            'bundle_reference' => $bundleReference,
            'status' => $success ? 'created' : 'partial',
            'customer_data' => $customer,
            'la_geria_order_id' => $laGeriaResult['order_id'] ?? null,
            'la_geria_order_number' => $laGeriaResult['order_number'] ?? null,
            'la_geria_status' => $laGeriaResult['status'] ?? null,
            'la_geria_total' => $laGeriaResult['total'] ?? null,
            'lanzaloe_order_id' => $lanzaloeResult['order_id'] ?? null,
            'lanzaloe_cart_id' => $lanzaloeResult['cart_id'] ?? null,
            'lanzaloe_status' => $lanzaloeResult['success'] ? 'created' : ($lanzaloeResult['stage'] ?? 'failed'),
            'lanzaloe_error' => $lanzaloeResult['error'] ?? null,
            'raw_result' => [
                'la_geria' => $laGeriaResult,
                'lanzaloe' => $lanzaloeResult,
            ],
        ]);

        return [
            'success' => $success,
            'la_geria' => $laGeriaResult,
            'lanzaloe' => $lanzaloeResult,
            'bundle_reference' => $bundleReference,
            'record_id' => $record->id,
        ];
    }

    /**
     * Create a pending order in La Geria's WooCommerce.
     *
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function createLaGeriaOrder(array $customer, array $item, string $status = 'pending'): array
    {
        if ($this->laGeriaClient === '' || $this->laGeriaSecret === '') {
            return ['success' => false, 'error' => 'Missing La Geria WooCommerce REST credentials'];
        }

        $payload = [
            'status' => $status,
            'payment_method' => 'bacs',
            'payment_method_title' => 'POC Bundle - Transferencia',
            'set_paid' => false,
            'billing' => [
                'first_name' => $customer['first_name'],
                'last_name' => $customer['last_name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
                'address_1' => $customer['address'],
                'city' => $customer['city'],
                'postcode' => $customer['postcode'],
                'country' => $customer['country'],
            ],
            'line_items' => [$item],
            'meta_data' => [
                ['key' => 'poc_bundle', 'value' => 'La Geria + Lanzaloe bundle'],
                ['key' => 'poc_source', 'value' => 'novagestion-bundle'],
            ],
        ];

        try {
            $response = $this->laGeriaHttp()
                ->withoutVerifying()
                ->post("{$this->laGeriaBaseUrl}/wp-json/wc/v3/orders", $payload);

            if (! $response->successful()) {
                return ['success' => false, 'error' => "HTTP {$response->status()}: {$response->body()}"];
            }

            $json = $response->json();

            return [
                'success' => true,
                'order_id' => $json['id'] ?? null,
                'order_number' => $json['number'] ?? null,
                'status' => $json['status'] ?? null,
                'total' => $json['total'] ?? null,
                'currency' => $json['currency'] ?? null,
            ];
        } catch (\Throwable $exception) {
            Log::error('La Geria bundle order creation failed', ['error' => $exception->getMessage()]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Create an order in Lanzaloe's Magento via admin customer-cart REST API.
     *
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createLanzaloeOrder(array $customer, array $item, array $options = []): array
    {
        if ($this->lanzaloeToken === '') {
            return ['success' => false, 'error' => 'Missing Lanzaloe Magento token'];
        }

        if ($this->useCustomOrderEndpoint) {
            return $this->createLanzaloeOrderViaCustomEndpoint($customer, $item, $options);
        }

        $storeUrl = $this->lanzaloeStoreUrl($options);
        $shippingMethodCode = $options['lanzaloe_shipping_method'] ?? 'amstrates7';
        $shippingCarrierCode = $options['lanzaloe_shipping_carrier'] ?? 'amstrates';
        $paymentMethod = $options['lanzaloe_payment_method'] ?? 'banktransfer';

        $address = [
            'countryId' => $customer['country'],
            'regionId' => $customer['region_id'] ?? null,
            'regionCode' => $customer['region_code'] ?? null,
            'region' => $customer['region'] ?? null,
            'street' => is_array($customer['street']) ? $customer['street'] : [$customer['street']],
            'telephone' => $customer['phone'],
            'postcode' => $customer['postcode'],
            'city' => $customer['city'],
            'firstname' => $customer['first_name'],
            'lastname' => $customer['last_name'],
            'company' => $customer['company'] ?? null,
            'email' => $customer['email'],
        ];

        $http = $this->lanzaloeHttp($storeUrl);

        try {
            $adminUrl = rtrim($this->lanzaloeBaseUrl, '/').'/rest/all/V1';
            $customerId = $this->ensureLanzaloeCustomer($http, $adminUrl, $customer);
            if ($customerId === null) {
                Log::info('Falling back to Lanzaloe custom endpoint due to customer REST failure', [
                    'email' => $customer['email'],
                ]);

                return $this->createLanzaloeOrderViaCustomEndpoint($customer, $item, $options);
            }

            $cartResponse = $http->post("{$storeUrl}/customers/{$customerId}/carts");
            if (! $cartResponse->successful()) {
                return ['success' => false, 'error' => "Cart creation failed: HTTP {$cartResponse->status()}: {$cartResponse->body()}"];
            }
            $cartId = (int) $cartResponse->body();

            $itemResponse = $http->post("{$storeUrl}/carts/{$cartId}/items", [
                'cartItem' => [
                    'quote_id' => (string) $cartId,
                    'sku' => $item['sku'],
                    'qty' => $item['quantity'] ?? 1,
                ],
            ]);
            if (! $itemResponse->successful()) {
                return ['success' => false, 'error' => "Add item failed: HTTP {$itemResponse->status()} - {$itemResponse->body()}"];
            }

            $shippingResponse = $http->post("{$storeUrl}/carts/{$cartId}/shipping-information", [
                'addressInformation' => [
                    'shipping_address' => $address,
                    'billing_address' => $address,
                    'shipping_method_code' => $shippingMethodCode,
                    'shipping_carrier_code' => $shippingCarrierCode,
                ],
            ]);
            if (! $shippingResponse->successful()) {
                return [
                    'success' => false,
                    'stage' => 'shipping',
                    'cart_id' => $cartId,
                    'error' => "HTTP {$shippingResponse->status()}: {$shippingResponse->body()}",
                ];
            }

            $paymentResponse = $http->put("{$storeUrl}/carts/{$cartId}/order", [
                'paymentMethod' => ['method' => $paymentMethod],
                'billingAddress' => $address,
                'shippingAddress' => $address,
            ]);
            if (! $paymentResponse->successful()) {
                return [
                    'success' => false,
                    'stage' => 'payment',
                    'cart_id' => $cartId,
                    'error' => "HTTP {$paymentResponse->status()}: {$paymentResponse->body()}",
                ];
            }

            $orderId = trim((string) $paymentResponse->body(), '"');

            return [
                'success' => true,
                'order_id' => $orderId,
                'cart_id' => $cartId,
                'customer_id' => $customerId,
            ];
        } catch (\Throwable $exception) {
            Log::error('Lanzaloe bundle order creation failed', ['error' => $exception->getMessage()]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Find or create a customer in Lanzaloe Magento via admin API.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $http
     * @param  array<string, mixed>  $customer
     * @return int|null
     */
    private function ensureLanzaloeCustomer(\Illuminate\Http\Client\PendingRequest $http, string $adminUrl, array $customer): ?int
    {
        try {
            $searchResponse = $http->get("{$adminUrl}/customers/search", [
                'searchCriteria[filterGroups][0][filters][0][field]' => 'email',
                'searchCriteria[filterGroups][0][filters][0][value]' => $customer['email'],
                'searchCriteria[filterGroups][0][filters][0][conditionType]' => 'eq',
            ]);

            if ($searchResponse->successful()) {
                $items = $searchResponse->json('items') ?? [];
                if ($items !== []) {
                    return (int) $items[0]['id'];
                }
            } else {
                Log::warning('Lanzaloe customer search failed', [
                    'email' => $customer['email'],
                    'status' => $searchResponse->status(),
                    'body' => $searchResponse->body(),
                ]);
            }

            $createResponse = $http->post("{$adminUrl}/customers", [
                'customer' => [
                    'email' => $customer['email'],
                    'firstname' => $customer['first_name'] ?? 'Nova',
                    'lastname' => $customer['last_name'] ?? 'Customer',
                    'store_id' => 1,
                    'website_id' => 1,
                ],
                'password' => $customer['password'] ?? uniqid('nova', true),
            ]);

            if ($createResponse->successful()) {
                return (int) $createResponse->json('id');
            }

            $createBody = $createResponse->body();
            Log::error('Lanzaloe customer creation failed', [
                'email' => $customer['email'],
                'status' => $createResponse->status(),
                'body' => $createBody,
            ]);

            // If email already exists globally, try a broader search.
            if ($createResponse->status() === 400 && str_contains(strtolower($createBody), 'ya existe')) {
                $fallback = $http->get("{$adminUrl}/customers/search", [
                    'searchCriteria[filterGroups][0][filters][0][field]' => 'email',
                    'searchCriteria[filterGroups][0][filters][0][value]' => '%'.$customer['email'].'%',
                    'searchCriteria[filterGroups][0][filters][0][conditionType]' => 'like',
                    'searchCriteria[pageSize]' => 100,
                ]);

                if ($fallback->successful()) {
                    foreach ($fallback->json('items') ?? [] as $item) {
                        if (strtolower((string) ($item['email'] ?? '')) === strtolower($customer['email'])) {
                            return (int) $item['id'];
                        }
                    }
                }

                Log::warning('Lanzaloe duplicate customer fallback search returned no match', [
                    'email' => $customer['email'],
                    'status' => $fallback->status(),
                    'body' => $fallback->body(),
                ]);
            }

            return null;
        } catch (\Throwable $exception) {
            Log::error('Lanzaloe ensure customer failed', [
                'email' => $customer['email'],
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a Lanzaloe order via the custom Novagestion Magento plugin.
     *
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function createLanzaloeOrderViaCustomEndpoint(array $customer, array $item, array $options): array
    {
        try {
            $shippingMethod = $options['lanzaloe_shipping_method'] ?? 'amstrates7';
            $shippingCarrier = $options['lanzaloe_shipping_carrier'] ?? 'amstrates';
            $paymentMethod = $options['lanzaloe_payment_method'] ?? 'banktransfer';
            $storeUrl = $options['lanzaloe_store_url']
                ?? config('services.lanzaloe.store_url')
                ?? $this->lanzaloeBaseUrl.'/rest/all/V1';

            $payload = [
                'sku' => $item['sku'],
                'qty' => $item['quantity'] ?? 1,
                'customer' => [
                    'email' => $customer['email'],
                    'firstname' => $customer['first_name'],
                    'lastname' => $customer['last_name'],
                    'telephone' => $customer['phone'],
                    'street' => is_array($customer['street']) ? $customer['street'] : [$customer['street']],
                    'city' => $customer['city'],
                    'postcode' => $customer['postcode'],
                    'country_id' => $customer['country'],
                    'region_code' => $customer['region_code'] ?? null,
                    'company' => $customer['company'] ?? null,
                ],
                'shippingMethod' => $shippingMethod,
                'shippingCarrier' => $shippingCarrier,
                'paymentMethod' => $paymentMethod,
            ];

            $response = $this->lanzaloeHttp($storeUrl)->post("{$storeUrl}/novagestion/create-order", $payload);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'stage' => 'custom_endpoint',
                    'error' => "HTTP {$response->status()}: {$response->body()}",
                ];
            }

            $json = $response->json();

            return [
                'success' => (bool) ($json['success'] ?? false),
                'order_id' => $json['order_id'] ?? null,
                'order_entity_id' => $json['order_entity_id'] ?? null,
                'customer_id' => $json['customer_id'] ?? null,
                'grand_total' => $json['grand_total'] ?? null,
                'message' => $json['message'] ?? null,
                'error' => $json['error'] ?? null,
            ];
        } catch (\Throwable $exception) {
            Log::error('Lanzaloe custom endpoint order creation failed', ['error' => $exception->getMessage()]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Cancel a La Geria order.
     */
    public function cancelLaGeriaOrder(int $orderId): array
    {
        try {
            $response = $this->laGeriaHttp()
                ->withoutVerifying()
                ->put("{$this->laGeriaBaseUrl}/wp-json/wc/v3/orders/{$orderId}", ['status' => 'cancelled']);

            return [
                'success' => $response->successful(),
                'status' => $response->json('status'),
                'error' => $response->successful() ? null : "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (\Throwable $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Cancel a Lanzaloe order.
     */
    public function cancelLanzaloeOrder(int $orderId): array
    {
        try {
            $response = $this->lanzaloeHttp($this->lanzaloeBaseUrl.'/rest/all/V1')
                ->post("{$this->lanzaloeBaseUrl}/rest/all/V1/orders/{$orderId}/cancel");

            return [
                'success' => $response->successful(),
                'error' => $response->successful() ? null : "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (\Throwable $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Confirm La Geria order as paid via WooCommerce API.
     */
    public function confirmLaGeriaPayment(int $orderId): array
    {
        try {
            $response = $this->laGeriaHttp()
                ->withoutVerifying()
                ->put("{$this->laGeriaBaseUrl}/wp-json/wc/v3/orders/{$orderId}", [
                    'status' => 'completed',
                    'set_paid' => true,
                ]);

            return [
                'success' => $response->successful(),
                'status' => $response->json('status'),
                'error' => $response->successful() ? null : "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (\Throwable $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Confirm Lanzaloe order as paid creating an invoice via Magento API.
     */
    public function confirmLanzaloePayment(int $orderId): array
    {
        try {
            $response = $this->lanzaloeHttp($this->lanzaloeBaseUrl.'/rest/all/V1')
                ->post("{$this->lanzaloeBaseUrl}/rest/all/V1/order/{$orderId}/invoice");

            return [
                'success' => $response->successful(),
                'error' => $response->successful() ? null : "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (\Throwable $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCustomer(array $data): array
    {
        return [
            'first_name' => $data['first_name'] ?? 'Prueba',
            'last_name' => $data['last_name'] ?? 'Nova',
            'email' => $data['email'] ?? 'poc@novagestion.eu',
            'phone' => $data['phone'] ?? '600000000',
            'address' => $data['address'] ?? 'Calle Prueba POC',
            'city' => $data['city'] ?? 'Arrecife',
            'postcode' => $data['postcode'] ?? '35500',
            'country' => $data['country'] ?? 'ES',
            'region_id' => $data['region_id'] ?? null,
            'region_code' => $data['region_code'] ?? null,
            'region' => $data['region'] ?? null,
            'street' => $data['street'] ?? [$data['address'] ?? 'Calle Prueba POC'],
            'company' => $data['company'] ?? null,
        ];
    }

    private function generateBundleReference(array $laGeria, array $lanzaloe): string
    {
        $laGeriaId = $laGeria['order_id'] ?? 'n/a';
        $lanzaloeId = $lanzaloe['order_id'] ?? ($lanzaloe['cart_id'] ?? 'n/a');

        return 'BUNDLE-'.date('Ymd-His')."-LG{$laGeriaId}-LZ{$lanzaloeId}";
    }

    private function laGeriaHttp(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($this->laGeriaClient, $this->laGeriaSecret)
            ->timeout(30)
            ->acceptJson()
            ->asJson();
    }

    private function lanzaloeHttp(string $baseUrl): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->withToken($this->lanzaloeToken)
            ->timeout(30)
            ->acceptJson()
            ->asJson();
    }

    private function lanzaloeStoreUrl(array $options): string
    {
        $url = $options['lanzaloe_store_url']
            ?? config('services.lanzaloe.store_url')
            ?? $this->lanzaloeBaseUrl.'/es/rest/es/V1';

        return rtrim($url, '/');
    }
}
