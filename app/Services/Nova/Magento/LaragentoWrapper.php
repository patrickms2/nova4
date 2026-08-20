<?php

declare(strict_types=1);

namespace App\Services\Nova\Magento;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class LaragentoWrapper
{
    private string $baseUrl;

    private string $apiToken;

    private int $timeout = 30;

    private int $retries = 3;

    public function __construct(string $baseUrl, string $apiToken)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiToken = $apiToken;
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function setRetries(int $count): self
    {
        $this->retries = $count;

        return $this;
    }

    /**
     * Create order in Magento
     */
    public function createOrder(array $data): array
    {
        try {
            $response = $this->http()->post('/orders', [
                'entity' => [
                    'customer_email' => $data['customer_email'] ?? null,
                    'customer_firstname' => $data['customer_firstname'] ?? null,
                    'customer_lastname' => $data['customer_lastname'] ?? null,
                    'customer_group_id' => $data['customer_group_id'] ?? 1,
                ],
                'items' => $this->normalizeOrderItems($data['items'] ?? []),
                'billing_address' => $this->normalizeAddress($data['billing_address'] ?? []),
                'shipping_address' => $this->normalizeAddress($data['shipping_address'] ?? []),
                'payment_method' => [
                    'method' => $data['payment_method'] ?? 'checkmo',
                ],
                'shipping_method' => $data['shipping_method'] ?? 'flatrate_flatrate',
            ]);

            $response->throw();

            return [
                'success' => true,
                'order_id' => $response->json('entity_id'),
                'order_number' => $response->json('increment_id'),
                'status' => $response->json('status'),
                'data' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            Log::error('LaragentoWrapper createOrder failed', [
                'error' => $exception->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Get product by SKU
     */
    public function getProduct(string $sku): array
    {
        try {
            $response = $this->http()->get("/products/{$sku}");

            $response->throw();

            return [
                'success' => true,
                'sku' => $response->json('sku'),
                'name' => $response->json('name'),
                'price' => $response->json('price'),
                'status' => $response->json('status'),
                'stock' => $response->json('extension_attributes.stock_item.qty'),
                'data' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            Log::error('LaragentoWrapper getProduct failed', [
                'sku' => $sku,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Search products
     */
    public function searchProducts(array $criteria = []): array
    {
        try {
            $response = $this->http()->get('/products', [
                'searchCriteria' => $criteria,
            ]);

            $response->throw();

            return [
                'success' => true,
                'total_count' => $response->json('total_count'),
                'items' => $response->json('items', []),
            ];
        } catch (\Throwable $exception) {
            Log::error('LaragentoWrapper searchProducts failed', [
                'criteria' => $criteria,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Create bulk operation
     */
    public function createBulk(array $operations): string
    {
        try {
            $response = $this->http()->post('/bulk', [
                'description' => 'Nova bulk sync',
                'operations' => $operations,
            ]);

            $response->throw();

            return $response->json('bulk_uuid');
        } catch (\Throwable $exception) {
            Log::error('LaragentoWrapper createBulk failed', [
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Get bulk operation status
     */
    public function getBulkStatus(string $bulkUuid): array
    {
        try {
            $response = $this->http()->get("/bulk/{$bulkUuid}");

            $response->throw();

            return [
                'success' => true,
                'status' => $response->json('status'),
                'operation_count' => $response->json('operation_count'),
                'operations' => $response->json('operations', []),
            ];
        } catch (\Throwable $exception) {
            Log::error('LaragentoWrapper getBulkStatus failed', [
                'bulk_uuid' => $bulkUuid,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Login as customer (for support)
     */
    public function impersonateCustomer(string $email): string
    {
        try {
            $response = $this->http()->post('/customers/me/login-as-customer', [
                'customer_email' => $email,
            ]);

            $response->throw();

            return $response->json('login_url');
        } catch (\Throwable $exception) {
            Log::error('LaragentoWrapper impersonateCustomer failed', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function http()
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->retry($this->retries, 100)
            ->withToken($this->apiToken);
    }

    private function normalizeOrderItems(array $items): array
    {
        return array_map(fn ($item) => [
            'sku' => $item['sku'] ?? $item['product_sku'] ?? null,
            'qty' => $item['quantity'] ?? $item['qty'] ?? 1,
            'price' => $item['price'] ?? null,
        ], $items);
    }

    private function normalizeAddress(array $address): array
    {
        return [
            'firstname' => $address['firstname'] ?? $address['first_name'] ?? null,
            'lastname' => $address['lastname'] ?? $address['last_name'] ?? null,
            'street' => $address['street'] ?? $address['address_line1'] ?? null,
            'city' => $address['city'] ?? null,
            'country_id' => $address['country_id'] ?? $address['country'] ?? 'ES',
            'postcode' => $address['postcode'] ?? $address['postal_code'] ?? null,
            'telephone' => $address['telephone'] ?? $address['phone'] ?? null,
        ];
    }
}
