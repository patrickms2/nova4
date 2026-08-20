<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaExternalCatalogItem;
use App\Models\NovaExternalOrder;
use App\Models\NovaIntegrationSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class NovaMagentoApiSyncService
{
    public function __construct(
        private readonly NovaExternalSyncSupport $support,
    ) {}

    public function sync(NovaIntegrationSetting $setting, bool $fullSync = false): array
    {
        $summary = [
            'products_processed' => 0,
            'orders_processed' => 0,
            'created' => 0,
            'updated' => 0,
        ];

        $this->support->markSyncStarted($setting);

        try {
            foreach ($this->getAllProducts($setting) as $product) {
                $payload = $this->normalizeProduct($setting, $product);
                $item = NovaExternalCatalogItem::query()->updateOrCreate(
                    [
                        'source' => 'magento',
                        'external_id' => $payload['external_id'],
                        'external_item_id' => null,
                    ],
                    $payload,
                );

                $summary[$item->wasRecentlyCreated ? 'created' : 'updated']++;
                $summary['products_processed']++;
            }

            try {
                foreach ($this->getAllOrders($setting, $fullSync) as $order) {
                    $payload = $this->normalizeOrder($setting, $order);
                    $externalOrder = NovaExternalOrder::query()->updateOrCreate(
                        [
                            'source' => 'magento',
                            'external_id' => $payload['external_id'],
                        ],
                        $payload,
                    );

                    $summary[$externalOrder->wasRecentlyCreated ? 'created' : 'updated']++;
                    $summary['orders_processed']++;
                }
            } catch (\Throwable $exception) {
                $summary['orders_error'] = $exception->getMessage();
            }

            $this->support->markSyncFinished($setting);
            $this->support->logSync($setting, 'nova:sync-magento', 'mixed', [
                'processed' => $summary['products_processed'] + $summary['orders_processed'],
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'detail' => $summary,
            ]);

            return $summary;
        } catch (\Throwable $exception) {
            $this->support->markSyncFailed($setting, $exception);
            $this->support->logSync($setting, 'nova:sync-magento', 'mixed', [
                'processed' => $summary['products_processed'] + $summary['orders_processed'],
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'detail' => $summary,
            ], 'failed', $exception);

            throw $exception;
        }
    }

    private function http(NovaIntegrationSetting $setting): PendingRequest
    {
        $credentials = $setting->credentials ?? [];
        $token = (string) data_get($credentials, 'access_token', data_get($credentials, 'token', ''));

        $baseUrl = rtrim((string) ($setting->api_url ?: $setting->base_url), '/');

        if (! str_contains($baseUrl, '/rest/')) {
            $baseUrl .= '/rest/V1';
        }

        $request = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout((int) data_get($setting->settings, 'timeout', 30))
            ->retry(3, 100);

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function getAllProducts(NovaIntegrationSetting $setting): \Generator
    {
        $page = 1;
        $pageSize = (int) data_get($setting->settings, 'page_size', 200);

        do {
            $response = $this->http($setting)->get('products', [
                'searchCriteria' => $this->buildSearchCriteria([], $page, $pageSize),
            ]);

            $response->throw();
            $payload = $response->json();
            $items = $payload['items'] ?? [];

            foreach ($items as $item) {
                yield $item;
            }

            $totalCount = (int) ($payload['total_count'] ?? 0);
            $hasMore = $page * $pageSize < $totalCount;
            $page++;
        } while ($hasMore);
    }

    private function getAllOrders(NovaIntegrationSetting $setting, bool $fullSync): \Generator
    {
        $page = 1;
        $pageSize = (int) data_get($setting->settings, 'page_size', 200);
        $filters = [
            [
                'field' => 'updated_at',
                'value' => $fullSync
                    ? (string) data_get($setting->settings, 'orders_full_sync_from', '2000-01-01 00:00:00')
                    : $this->support->computeSyncSince($setting)->format('Y-m-d H:i:s'),
                'condition' => 'gteq',
            ],
        ];

        do {
            $response = $this->http($setting)->get('orders', [
                'searchCriteria' => $this->buildSearchCriteria($filters, $page, $pageSize),
            ]);

            $response->throw();
            $payload = $response->json();
            $items = $payload['items'] ?? [];

            foreach ($items as $item) {
                yield $item;
            }

            $totalCount = (int) ($payload['total_count'] ?? 0);
            $hasMore = $page * $pageSize < $totalCount;
            $page++;
        } while ($hasMore);
    }

    private function buildSearchCriteria(array $filters, int $page, int $pageSize): array
    {
        $criteria = [
            'currentPage' => $page,
            'pageSize' => $pageSize,
        ];

        foreach ($filters as $index => $filter) {
            $criteria['filterGroups'][$index]['filters'][] = [
                'field' => $filter['field'],
                'value' => $filter['value'],
                'conditionType' => $filter['condition'] ?? 'eq',
            ];
        }

        return $criteria;
    }

    private function normalizeProduct(NovaIntegrationSetting $setting, array $product): array
    {
        $externalId = (string) ($product['id'] ?? $product['sku'] ?? '');
        $customAttributes = collect($product['custom_attributes'] ?? [])->pluck('value', 'attribute_code');

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'nova_integration_setting_id' => $setting->id,
            'source' => 'magento',
            'external_id' => $externalId,
            'external_item_id' => null,
            'type' => 'product',
            'status' => (string) ($product['status'] ?? ''),
            'name' => $product['name'] ?? 'Producto Magento',
            'description' => $customAttributes->get('description'),
            'short_description' => $customAttributes->get('short_description'),
            'sku' => $product['sku'] ?? null,
            'price' => $product['price'] ?? null,
            'regular_price' => $product['price'] ?? null,
            'currency' => (string) data_get($setting->settings, 'currency', 'EUR'),
            'metadata' => ['raw' => $product],
            'source_updated_at' => isset($product['updated_at']) ? CarbonImmutable::parse($product['updated_at']) : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'magento', 'id' => $externalId, 'sku' => $product['sku'] ?? null])),
            'last_synced_at' => now(),
        ];
    }

    private function normalizeOrder(NovaIntegrationSetting $setting, array $order): array
    {
        $externalId = (string) ($order['entity_id'] ?? '');

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'source' => 'magento',
            'external_id' => $externalId,
            'external_increment_id' => $order['increment_id'] ?? null,
            'status' => $order['status'] ?? null,
            'payment_status' => $this->derivePaymentStatus($order),
            'customer_name' => $this->customerName($order),
            'customer_email' => $order['customer_email'] ?? null,
            'subtotal' => $order['subtotal'] ?? null,
            'tax_amount' => $order['tax_amount'] ?? null,
            'shipping_amount' => $order['shipping_amount'] ?? null,
            'discount_amount' => abs((float) ($order['discount_amount'] ?? 0)),
            'grand_total' => $order['grand_total'] ?? null,
            'currency' => $order['order_currency_code'] ?? (string) data_get($setting->settings, 'currency', 'EUR'),
            'payment_method' => data_get($order, 'payment.method'),
            'shipping_method' => $order['shipping_description'] ?? null,
            'ordered_at' => isset($order['created_at']) ? CarbonImmutable::parse($order['created_at']) : null,
            'items' => $order['items'] ?? [],
            'metadata' => ['raw' => $order],
            'source_updated_at' => isset($order['updated_at']) ? CarbonImmutable::parse($order['updated_at']) : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'magento', 'id' => $externalId])),
            'last_synced_at' => now(),
        ];
    }

    private function derivePaymentStatus(array $order): string
    {
        $grandTotal = (float) ($order['grand_total'] ?? 0);
        $totalPaid = (float) ($order['total_paid'] ?? 0);
        $totalDue = (float) ($order['total_due'] ?? 0);
        $totalRefunded = (float) ($order['total_refunded'] ?? 0);
        $status = (string) ($order['status'] ?? '');

        if ($totalRefunded > 0 && $totalRefunded >= $totalPaid) {
            return 'refunded';
        }

        if (($totalDue === 0.0 && $totalPaid > 0) || ($totalPaid >= $grandTotal && $grandTotal > 0)) {
            return 'paid';
        }

        if (in_array($status, ['canceled', 'closed'], true)) {
            return 'failed';
        }

        return 'pending';
    }

    private function customerName(array $order): string
    {
        $name = trim(implode(' ', array_filter([(string) ($order['customer_firstname'] ?? ''), (string) ($order['customer_lastname'] ?? '')])));

        return $name !== '' ? $name : 'Guest';
    }
}
