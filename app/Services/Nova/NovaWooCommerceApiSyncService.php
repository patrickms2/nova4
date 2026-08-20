<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaExternalCatalogItem;
use App\Models\NovaExternalOrder;
use App\Models\NovaIntegrationSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class NovaWooCommerceApiSyncService
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
            foreach ($this->getAllProducts($setting, $fullSync) as $product) {
                $payload = $this->normalizeProduct($setting, $product);
                $item = NovaExternalCatalogItem::query()->updateOrCreate(
                    [
                        'source' => 'woo',
                        'external_id' => $payload['external_id'],
                        'external_item_id' => null,
                    ],
                    $payload,
                );

                $summary[$item->wasRecentlyCreated ? 'created' : 'updated']++;
                $summary['products_processed']++;
            }

            foreach ($this->getAllOrders($setting, $fullSync) as $order) {
                $payload = $this->normalizeOrder($setting, $order);
                $externalOrder = NovaExternalOrder::query()->updateOrCreate(
                    [
                        'source' => 'woo',
                        'external_id' => $payload['external_id'],
                    ],
                    $payload,
                );

                $summary[$externalOrder->wasRecentlyCreated ? 'created' : 'updated']++;
                $summary['orders_processed']++;
            }

            $this->support->markSyncFinished($setting);
            $this->support->logSync($setting, 'nova:sync-woo-api', 'mixed', [
                'processed' => $summary['products_processed'] + $summary['orders_processed'],
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'detail' => $summary,
            ]);

            return $summary;
        } catch (\Throwable $exception) {
            $this->support->markSyncFailed($setting, $exception);
            $this->support->logSync($setting, 'nova:sync-woo-api', 'mixed', [
                'processed' => $summary['products_processed'] + $summary['orders_processed'],
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'detail' => $summary,
            ], 'failed', $exception);

            throw $exception;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchProducts(NovaIntegrationSetting $setting, array $query = []): array
    {
        $response = $this->http($setting)->get('products', array_merge([
            'per_page' => (int) ($query['per_page'] ?? 10),
            'page' => (int) ($query['page'] ?? 1),
            'orderby' => $query['orderby'] ?? 'modified',
            'order' => $query['order'] ?? 'desc',
        ], $query));

        $response->throw();

        return $response->json() ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchOrders(NovaIntegrationSetting $setting, array $query = []): array
    {
        $response = $this->http($setting)->get('orders', array_merge([
            'per_page' => (int) ($query['per_page'] ?? 10),
            'page' => (int) ($query['page'] ?? 1),
            'orderby' => $query['orderby'] ?? 'date',
            'order' => $query['order'] ?? 'desc',
        ], $query));

        $response->throw();

        return $response->json() ?? [];
    }

    private function http(NovaIntegrationSetting $setting): PendingRequest
    {
        $credentials = $this->credentialsFor($setting);

        $request = Http::baseUrl(rtrim((string) $setting->base_url, '/').'/wp-json/wc/v3')
            ->acceptJson()
            ->timeout((int) data_get($setting->settings, 'timeout', 30));

        $host = parse_url((string) $setting->base_url, PHP_URL_HOST);

        if (is_string($host) && str_ends_with($host, '.test')) {
            $request = $request->withoutVerifying();
        }

        if (data_get($setting->settings, 'verify_ssl') === false) {
            $request = $request->withoutVerifying();
        }

        if ($credentials['consumer_key'] !== '' && $credentials['consumer_secret'] !== '') {
            return $request->withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret']);
        }

        if ($credentials['username'] !== '' && $credentials['application_password'] !== '') {
            return $request->withBasicAuth($credentials['username'], $credentials['application_password']);
        }

        return $request;
    }

    /**
     * @return array{consumer_key: string, consumer_secret: string, username: string, application_password: string}
     */
    private function credentialsFor(NovaIntegrationSetting $setting): array
    {
        $credentials = $setting->credentials ?? [];
        $envGroup = (string) data_get($setting->settings, 'env_group', '');

        return [
            'consumer_key' => $this->credentialValue($credentials, 'consumer_key')
                ?: $this->envValue($envGroup === '' ? null : $envGroup.'_WOO_REST_API_CLIENT')
                ?: $this->envValue($envGroup === '' ? null : $envGroup.'_WOOCOMMERCE_CONSUMER_KEY')
                ?: $this->envValue($envGroup === '' ? null : $envGroup.'_CONSUMER_KEY'),
            'consumer_secret' => $this->credentialValue($credentials, 'consumer_secret')
                ?: $this->envValue($envGroup === '' ? null : $envGroup.'_WOO_REST_API_CLIENT_SECRET')
                ?: $this->envValue($envGroup === '' ? null : $envGroup.'_WOOCOMMERCE_CONSUMER_SECRET')
                ?: $this->envValue($envGroup === '' ? null : $envGroup.'_CONSUMER_SECRET'),
            'username' => $this->credentialValue($credentials, 'username')
                ?: $this->credentialValue($credentials, 'user')
                ?: $this->envValue($envGroup === '' ? null : $envGroup.'_WP_USER'),
            'application_password' => $this->credentialValue($credentials, 'application_password')
                ?: $this->credentialValue($credentials, 'password')
                ?: $this->envValue($envGroup === '' ? null : $envGroup.'_WP_APPLICATION_PASSWORD'),
        ];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function credentialValue(array $credentials, string $key): string
    {
        $value = data_get($credentials, $key);

        return (string) ($this->envValue($value) ?: $value ?: '');
    }

    private function envValue(mixed $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        $value = env((string) $name);

        return blank($value) ? null : (string) $value;
    }

    private function getAllProducts(NovaIntegrationSetting $setting, bool $fullSync): \Generator
    {
        $page = 1;
        $perPage = (int) data_get($setting->settings, 'page_size', 100);
        $params = [
            'per_page' => $perPage,
            'orderby' => 'modified',
            'order' => 'desc',
        ];

        if (! $fullSync) {
            $params['modified_after'] = $this->support->computeSyncSince($setting)->toIso8601String();
        }

        do {
            $response = $this->http($setting)->get('products', array_merge($params, ['page' => $page]));
            $response->throw();
            $items = $response->json() ?? [];

            foreach ($items as $item) {
                yield $item;
            }

            $hasMore = count($items) === $perPage;
            $page++;
        } while ($hasMore);
    }

    private function getAllOrders(NovaIntegrationSetting $setting, bool $fullSync): \Generator
    {
        $page = 1;
        $perPage = (int) data_get($setting->settings, 'page_size', 100);
        $params = [
            'per_page' => $perPage,
            'orderby' => 'modified',
            'order' => 'desc',
        ];

        if (! $fullSync) {
            $params['modified_after'] = $this->support->computeSyncSince($setting)->toIso8601String();
        }

        do {
            $response = $this->http($setting)->get('orders', array_merge($params, ['page' => $page]));
            $response->throw();
            $items = $response->json() ?? [];

            foreach ($items as $item) {
                yield $item;
            }

            $hasMore = count($items) === $perPage;
            $page++;
        } while ($hasMore);
    }

    private function normalizeProduct(NovaIntegrationSetting $setting, array $product): array
    {
        $externalId = (string) ($product['id'] ?? '');

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'nova_integration_setting_id' => $setting->id,
            'source' => 'woo',
            'external_id' => $externalId,
            'external_item_id' => null,
            'type' => 'product',
            'status' => $product['status'] ?? null,
            'name' => $product['name'] ?? 'Producto WooCommerce',
            'description' => $product['description'] ?? null,
            'short_description' => $product['short_description'] ?? null,
            'sku' => $product['sku'] ?? null,
            'price' => $this->decimalOrNull($product['price'] ?? null),
            'regular_price' => $this->decimalOrNull($product['regular_price'] ?? null),
            'currency' => (string) data_get($setting->settings, 'currency', 'EUR'),
            'stock_status' => $product['stock_status'] ?? null,
            'image_url' => data_get($product, 'images.0.src'),
            'purchase_url' => $product['permalink'] ?? null,
            'metadata' => ['raw' => $product],
            'source_updated_at' => isset($product['date_modified_gmt']) ? CarbonImmutable::parse($product['date_modified_gmt'], 'UTC') : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'woo', 'id' => $externalId, 'sku' => $product['sku'] ?? null])),
            'last_synced_at' => now(),
        ];
    }

    private function normalizeOrder(NovaIntegrationSetting $setting, array $order): array
    {
        $externalId = (string) ($order['id'] ?? '');
        $billing = $order['billing'] ?? [];
        $customerName = trim(implode(' ', array_filter([(string) ($billing['first_name'] ?? ''), (string) ($billing['last_name'] ?? '')])));

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'source' => 'woo',
            'external_id' => $externalId,
            'external_increment_id' => $order['number'] ?? null,
            'status' => $order['status'] ?? null,
            'payment_status' => $this->paymentStatus($order['status'] ?? null),
            'customer_name' => $customerName !== '' ? $customerName : null,
            'customer_email' => $billing['email'] ?? null,
            'subtotal' => null,
            'tax_amount' => $this->decimalOrNull($order['total_tax'] ?? null),
            'shipping_amount' => $this->decimalOrNull($order['shipping_total'] ?? null),
            'discount_amount' => $this->decimalOrNull($order['discount_total'] ?? null),
            'grand_total' => $this->decimalOrNull($order['total'] ?? null),
            'currency' => $order['currency'] ?? 'EUR',
            'payment_method' => $order['payment_method'] ?? null,
            'shipping_method' => data_get($order, 'shipping_lines.0.method_title'),
            'ordered_at' => isset($order['date_created_gmt']) ? CarbonImmutable::parse($order['date_created_gmt'], 'UTC') : null,
            'admin_url' => $this->adminUrl($setting, $externalId),
            'items' => $order['line_items'] ?? [],
            'metadata' => ['raw' => $order],
            'source_updated_at' => isset($order['date_modified_gmt']) ? CarbonImmutable::parse($order['date_modified_gmt'], 'UTC') : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'woo', 'id' => $externalId])),
            'last_synced_at' => now(),
        ];
    }

    private function paymentStatus(?string $status): string
    {
        return match ($status) {
            'completed', 'processing' => 'paid',
            'refunded' => 'refunded',
            'failed', 'cancelled' => 'failed',
            default => 'pending',
        };
    }

    private function decimalOrNull(mixed $value): ?float
    {
        if (blank($value)) {
            return null;
        }

        return (float) $value;
    }

    private function adminUrl(NovaIntegrationSetting $setting, string $orderId): ?string
    {
        if (blank($setting->base_url) || blank($orderId)) {
            return null;
        }

        $path = (string) data_get($setting->settings, 'woocommerce_admin_path', 'wp-admin/post.php?post={id}&action=edit');

        return rtrim((string) $setting->base_url, '/').'/'.ltrim(str_replace('{id}', $orderId, $path), '/');
    }
}
