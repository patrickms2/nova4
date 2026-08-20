<?php

namespace App\Services\Magento;

use App\Contracts\Magento\MagentoApiClientInterface;
use App\Models\MagentoStore;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MagentoApiClient implements MagentoApiClientInterface
{
    /**
     * HTTP timeout in seconds for Magento API requests
     */
    private const HTTP_TIMEOUT_SECONDS = 30;

    /**
     * Maximum number of retry attempts for failed requests
     */
    private const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Delay in milliseconds between retry attempts
     */
    private const RETRY_DELAY_MS = 100;

    private PendingRequest $http;

    public function __construct(
        private readonly MagentoStore $store,
    )
    {
        $this->http = Http::baseUrl($this->store->getApiEndpoint())
            ->withToken($this->store->access_token)
            ->timeout(self::HTTP_TIMEOUT_SECONDS)
            ->retry(self::MAX_RETRY_ATTEMPTS, self::RETRY_DELAY_MS, function (\Exception $exception) {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException
                        && $exception->response->serverError());
            });
    }

    public function testConnection(): array
    {
        $response = $this->http->get('store/storeConfigs');

        if ($response->failed()) {
            $message = $response->json('message') ?? $response->body();
            throw new \RuntimeException("Failed to connect: {$message}");
        }

        return $response->json();
    }

    public function getOrders(
        array $filters = [],
        int   $page = 1,
        int   $pageSize = 500,
    ): array
    {
        $searchCriteria = $this->buildSearchCriteria($filters, $page, $pageSize);

        $response = $this->http->get('orders', [
            'searchCriteria' => $searchCriteria,
        ]);

        if ($response->failed()) {
            Log::error('Magento API Error', [
                'store_id' => $this->store->id,
                'endpoint' => 'GET /orders',
                'status' => $response->status(),
                'error' => $this->sanitizeResponseForLogging($response->body()),
            ]);

            throw new \RuntimeException(
                'Failed to fetch orders: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    public function getProducts(int $page = 1, int $pageSize = 500): array
    {
        $searchCriteria = $this->buildSearchCriteria([], $page, $pageSize);

        $response = $this->http->get('products', [
            'searchCriteria' => $searchCriteria,
        ]);

        if ($response->failed()) {
            Log::error('Magento API Error', [
                'store_id' => $this->store->id,
                'endpoint' => 'GET /products',
                'status' => $response->status(),
                'error' => $this->sanitizeResponseForLogging($response->body()),
            ]);

            throw new \RuntimeException(
                'Failed to fetch products: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    public function getAllProducts(int $pageSize = 500): \Generator
    {
        $page = 1;
        do {
            $response = $this->getProducts($page, $pageSize);
            $products = $response['items'] ?? [];

            foreach ($products as $product) {
                yield $product;
            }

            $totalCount = $response['total_count'] ?? 0;
            $readCount = ($page * $pageSize);
            $hasMore = $readCount < $totalCount;
            $page++;
        } while ($hasMore);
    }

    public function getOrdersSince(\DateTimeInterface $since, int $page = 1, int $pageSize = 500): array
    {
        return $this->getOrders([
            [
                'field' => 'updated_at',
                'value' => $since->format('Y-m-d H:i:s'),
                'condition' => 'gteq',
            ],
        ], $page, $pageSize);
    }

    public function getOrdersLastDays(int $days = 1, int $page = 1, int $pageSize = 500): array
    {
        $since = now()->subDays($days);

        return $this->getOrdersSince($since, $page, $pageSize);
    }

    public function getAllOrders(array $filters = [], int $pageSize = 500): \Generator
    {
        $page = 1;
        do {
            $response = $this->getOrders($filters, $page, $pageSize);
            $orders = $response['items'] ?? [];

            foreach ($orders as $order) {
                yield $order;
            }

            $totalCount = $response['total_count'] ?? 0;
            $readCount = ($page * $pageSize);
            $hasMore = $readCount < $totalCount;
            $page++;
        } while ($hasMore);
    }

    public function getOrder(int $orderId): array
    {
        $response = $this->http->get("orders/{$orderId}");

        if ($response->failed()) {
            throw new \RuntimeException(
                'Failed to fetch order: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    /**
     * Get invoices with optional filters
     *
     * @param array $filters Array of filter conditions
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array Invoices response with items and total_count
     */
    public function getInvoices(
        array $filters = [],
        int   $page = 1,
        int   $pageSize = 500,
    ): array
    {
        $searchCriteria = $this->buildSearchCriteria($filters, $page, $pageSize);

        $response = $this->http->get('invoices', [
            'searchCriteria' => $searchCriteria,
        ]);

        if ($response->failed()) {
            Log::error('Magento API Error - Invoices', [
                'store_id' => $this->store->id,
                'endpoint' => 'GET /invoices',
                'status' => $response->status(),
                'error' => $this->sanitizeResponseForLogging($response->body()),
            ]);

            throw new \RuntimeException(
                'Failed to fetch invoices: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    /**
     * Get invoices for a specific order
     *
     * @param int $orderId The Magento order entity ID
     * @return array Array of invoice items
     */
    public function getInvoicesForOrder(int $orderId): array
    {
        $response = $this->getInvoices([
            [
                'field' => 'order_id',
                'value' => $orderId,
                'condition' => 'eq',
            ],
        ], 1, 500);

        return $response['items'] ?? [];
    }

    /**
     * Get shipments with optional filters
     *
     * @param array $filters Array of filter conditions
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array Shipments response with items and total_count
     */
    public function getShipments(
        array $filters = [],
        int   $page = 1,
        int   $pageSize = 500,
    ): array
    {
        $searchCriteria = $this->buildSearchCriteria($filters, $page, $pageSize);

        $response = $this->http->get('shipments', [
            'searchCriteria' => $searchCriteria,
        ]);

        if ($response->failed()) {
            Log::error('Magento API Error - Shipments', [
                'store_id' => $this->store->id,
                'endpoint' => 'GET /shipments',
                'status' => $response->status(),
                'error' => $this->sanitizeResponseForLogging($response->body()),
            ]);

            throw new \RuntimeException(
                'Failed to fetch shipments: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    /**
     * Get shipments for a specific order
     *
     * @param int $orderId The Magento order entity ID
     * @return array Array of shipment items
     */
    public function getShipmentsForOrder(int $orderId): array
    {
        $response = $this->getShipments([
            [
                'field' => 'order_id',
                'value' => $orderId,
                'condition' => 'eq',
            ],
        ], 1, 500);

        return $response['items'] ?? [];
    }

    /**
     * Cancel an order in Magento
     *
     * Sends a POST request to /rest/V1/orders/{id}/cancel endpoint.
     * This will:
     * - Set order status to 'canceled'
     * - Release inventory back to stock
     * - Prevent further modifications to the order
     *
     * Common error scenarios:
     * - 404: Order not found
     * - 400: Order already canceled, shipped, or invoiced
     * - 403: Insufficient permissions
     *
     * @param int $orderId The Magento order entity ID (not increment_id)
     * @return array Response data from Magento (typically boolean success indicator)
     *
     * @throws \RuntimeException When cancellation fails with error details
     */
    public function cancelOrder(int $orderId): array
    {
        Log::info('Attempting to cancel order in Magento', [
            'store_id' => $this->store->id,
            'magento_order_id' => $orderId,
        ]);

        $response = $this->http->post("orders/{$orderId}/cancel");

        if ($response->failed()) {
            $statusCode = $response->status();
            $errorMessage = $response->json('message') ?? 'Unknown error';
            $errorDetails = $response->json('parameters') ?? [];

            Log::error('Magento order cancellation failed', [
                'store_id' => $this->store->id,
                'magento_order_id' => $orderId,
                'status' => $statusCode,
                'error' => $errorMessage,
                'details' => $errorDetails,
            ]);

            // Provide more context for common error scenarios
            $contextualMessage = match ($statusCode) {
                404 => "Order #{$orderId} not found in Magento",
                400 => "Cannot cancel order #{$orderId}: {$errorMessage}",
                403 => "Insufficient permissions to cancel order #{$orderId}",
                default => "Failed to cancel order #{$orderId}: {$errorMessage}",
            };

            throw new \RuntimeException($contextualMessage, $statusCode);
        }

        Log::info('Order successfully canceled in Magento', [
            'store_id' => $this->store->id,
            'magento_order_id' => $orderId,
            'response' => $response->json(),
        ]);

        return $response->json() ?? ['success' => true];
    }

    /**
     * Update an order in Magento.
     *
     * @param array $orderData The order data to update (including entity_id)
     * @return array Update response data
     *
     * @throws \RuntimeException On update failure
     */
    public function updateOrder(array $orderData): array
    {
        $response = $this->http->post('orders', [
            'entity' => $orderData,
        ]);

        if ($response->failed()) {
            Log::error('Magento order update failed', [
                'store_id' => $this->store->id,
                'status' => $response->status(),
                'error' => $response->json('message') ?? $response->body(),
                'data' => $orderData,
            ]);

            throw new \RuntimeException(
                'Failed to update order: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    public function getCatalogRules(int $page = 1, int $pageSize = 500): array
    {
        $searchCriteria = $this->buildSearchCriteria([], $page, $pageSize);

        // Standard Magento 2 catalog rules endpoint
        $response = $this->http->get('catalogPriceRules/search', [
            'searchCriteria' => $searchCriteria,
        ]);

        if ($response->status() === 404) {
            $response = $this->http->get('catalogRules/search', [
                'searchCriteria' => $searchCriteria,
            ]);
        }

        if ($response->status() === 404) {
            $response = $this->http->get('catalogRules', [
                'searchCriteria' => $searchCriteria,
            ]);
        }

        if ($response->failed()) {
            Log::error('Magento API Error', [
                'store_id' => $this->store->id,
                'endpoint' => 'GET catalogRules',
                'status' => $response->status(),
                'error' => $this->sanitizeResponseForLogging($response->body()),
            ]);

            throw new \RuntimeException(
                'Failed to fetch catalog rules: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    public function getCartRules(int $page = 1, int $pageSize = 700): array
    {
        // Try with simple search criteria first
        $response = $this->http->get('salesRules/search', [
            'searchCriteria[currentPage]' => $page,
            'searchCriteria[pageSize]' => $pageSize,
        ]);

        if ($response->status() === 500) {
            // Try without any criteria as fallback
            $response = $this->http->get('salesRules/search');
        }

        if ($response->failed()) {
            $errorBody = $response->body();
            Log::error('Magento API Error', [
                'store_id' => $this->store->id,
                'endpoint' => 'GET salesRules/search',
                'status' => $response->status(),
                'error' => $this->sanitizeResponseForLogging($errorBody),
            ]);

            // Check if it's a 500 with a Magento Report ID
            if ($response->status() === 500 && str_contains($errorBody, 'Report ID:')) {
                preg_match('/Report ID: ([a-zA-Z0-9-]+)/', $errorBody, $matches);
                $reportId = $matches[1] ?? 'unknown';
                Log::warning("Magento Server Error 500 with Report ID: {$reportId}. This usually requires checking Magento server logs.");
            }

            throw new \RuntimeException(
                'Failed to fetch cart rules: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    public function createCartRule(array $payload): array
    {
        $response = $this->http->post('salesRules', $payload);

        if ($response->failed()) {
            Log::error('Magento API Error', [
                'store_id' => $this->store->id,
                'endpoint' => 'POST salesRules',
                'status' => $response->status(),
                'error' => $this->sanitizeResponseForLogging($response->body()),
                'payload' => $payload,
            ]);

            throw new \RuntimeException(
                'Failed to create cart rule: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    public function getWebsiteIds(): array
    {
        $response = $this->http->get('store/websites');

        if ($response->failed()) {
            throw new \RuntimeException(
                'Failed to fetch Magento website IDs: ' . ($response->json('message') ?? $response->status())
            );
        }

        return collect($response->json())
            ->pluck('id')
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->values()
            ->all();
    }

    public function getCustomerGroupIds(): array
    {
        $response = $this->http->get('customerGroups/search', [
            'searchCriteria[currentPage]' => 1,
            'searchCriteria[pageSize]' => 500,
        ]);

        if ($response->status() === 404) {
            $response = $this->http->get('customerGroups');
        }

        if ($response->failed()) {
            throw new \RuntimeException(
                'Failed to fetch Magento customer groups: ' . ($response->json('message') ?? $response->status())
            );
        }

        $items = $response->json('items') ?? $response->json() ?? [];

        return collect($items)
            ->pluck('id')
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id >= 0)
            ->values()
            ->all();
    }

    public function getCmsBlock(int $blockId): array
    {
        $response = $this->http->get("cmsBlock/{$blockId}");

        if ($response->failed()) {
            throw new \RuntimeException(
                'Failed to fetch CMS block: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    public function updateCmsBlock(int $blockId, array $payload): array
    {
        $response = $this->http->put("cmsBlock/{$blockId}", [
            'block' => $payload,
        ]);

        if ($response->failed()) {
            Log::error('Magento API Error', [
                'store_id' => $this->store->id,
                'endpoint' => "PUT cmsBlock/{$blockId}",
                'status' => $response->status(),
                'error' => $this->sanitizeResponseForLogging($response->body()),
                'payload' => $payload,
            ]);

            throw new \RuntimeException(
                'Failed to update CMS block: ' . ($response->json('message') ?? $response->status())
            );
        }

        return $response->json();
    }

    /**
     * Sanitize response body for logging to prevent sensitive data leaks
     *
     * @param string $body Raw response body
     * @return string Sanitized message for logging
     */
    /**
     * Get stock item for a product by SKU.
     */
    public function getStockItem(string $sku): ?array
    {
        try {
            $response = $this->http->get("stockItems/{$sku}");

            if ($response->successful()) {
                return $response->json();
            }

            // Stock item not found or error
            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch stock item', [
                'sku' => $sku,
                'store_id' => $this->store->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function sanitizeResponseForLogging(string $body): string
    {
        // Try to parse as JSON
        $json = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            // If it's JSON, only log the error message if present
            if (isset($json['message'])) {
                return 'Error: ' . $json['message'];
            }

            // Log structure but not actual data
            return 'Response contains: ' . implode(', ', array_keys($json));
        }

        // For non-JSON, only return truncated preview (no sensitive data)
        return 'Response: ' . substr($body, 0, 500) . '...';
    }

    private function buildSearchCriteria(
        array $filters,
        int   $page,
        int   $pageSize,
    ): array
    {
        $criteria = [
            'currentPage' => $page,
            'pageSize' => $pageSize,
            'sortOrders' => [
                ['field' => 'updated_at', 'direction' => 'DESC'],
            ],
        ];

        $filterGroups = [];
        foreach ($filters as $index => $filter) {
            $filterGroups[$index]['filters'][] = [
                'field' => $filter['field'],
                'value' => $filter['value'],
                'conditionType' => $filter['condition'] ?? 'eq',
            ];
        }

        if (!empty($filterGroups)) {
            $criteria['filterGroups'] = $filterGroups;
        }

        return $criteria;
    }
}
