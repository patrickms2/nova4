<?php

namespace App\Services\Bookings;

use App\Models\Customer;
use App\Models\IntegrationLink;
use App\Models\IntegrationSetting;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WordPressCatalogSyncService
{
    public function __construct(
        private readonly BookingSyncService $bookingSyncService,
    ) {
    }

    public function syncLatePointServices(?array $rows = null, bool $fullSync = false): void
    {
        $settings = $this->getActiveSettings();

        if (! $settings) {
            return;
        }

        $rows ??= $this->fetchLatePointServices($settings, $fullSync);

        foreach ($rows as $row) {
            $payload = $this->normalizeLatePointServiceRow($row);

            $service = Service::query()->updateOrCreate(
                [
                    'source' => 'latepoint',
                    'external_id' => $payload['external_id'],
                ],
                $payload,
            );

            $this->upsertIntegrationLink($service, $payload['source'], $payload['external_id'], null, null, null, $payload['meta'] ?? null, $payload['source_updated_at'] ?? null);
        }
    }

    public function syncWooProducts(?array $rows = null, bool $fullSync = false): void
    {
        $settings = $this->getActiveSettings();

        if (! $settings) {
            return;
        }

        $rows ??= $this->fetchWooProducts($settings, $fullSync);

        foreach ($rows as $row) {
            $payload = $this->normalizeWooProductRow($row);

            $service = Service::query()->updateOrCreate(
                [
                    'source' => 'woo',
                    'external_id' => $payload['external_id'],
                ],
                $payload,
            );

            $this->upsertIntegrationLink($service, $payload['source'], $payload['external_id'], null, null, null, $payload['meta'] ?? null, $payload['source_updated_at'] ?? null);
        }
    }

    public function syncLatePointCustomers(?array $rows = null, bool $fullSync = false): void
    {
        $settings = $this->getActiveSettings();

        if (! $settings) {
            return;
        }

        $rows ??= $this->fetchLatePointCustomers($settings, $fullSync);

        foreach ($rows as $row) {
            $payload = $this->normalizeLatePointCustomerRow($row);
            $customer = $this->upsertCustomer($payload);

            $this->upsertIntegrationLink($customer, 'latepoint', (string) ($payload['latepoint_customer_id'] ?? ''), null, null, null, $payload['meta'] ?? null, $payload['source_updated_at'] ?? null);
        }
    }

    public function syncWooCustomers(?array $rows = null, bool $fullSync = false): void
    {
        $settings = $this->getActiveSettings();

        if (! $settings) {
            return;
        }

        $rows ??= $this->fetchWooCustomerRowsFromOrders($settings, $fullSync);

        foreach ($rows as $row) {
            $payload = $this->normalizeWooCustomerRow($row);

            if (blank($payload['email'] ?? null)) {
                continue;
            }

            $customer = $this->upsertCustomer($payload);

            $externalId = filled($payload['woo_customer_user_id'] ?? null)
                ? (string) $payload['woo_customer_user_id']
                : (string) $payload['email'];

            $this->upsertIntegrationLink($customer, 'woo', $externalId, null, null, $payload['email'] ?? null, $payload['meta'] ?? null, $payload['source_updated_at'] ?? null);
        }
    }

    public function syncWooInvoices(?array $rows = null, bool $fullSync = false): void
    {
        $settings = $this->getActiveSettings();

        if (! $settings) {
            return;
        }

        $rows ??= $this->fetchWooInvoiceRowsFromOrders($settings, $fullSync);

        foreach ($rows as $row) {
            $payload = $this->normalizeWooInvoiceRow($row);

            $customerId = null;

            if (filled($payload['customer_email'] ?? null)) {
                $customer = Customer::query()->where('email', $payload['customer_email'])->first();
                $customerId = $customer?->id;
            }

            $invoice = Invoice::query()->updateOrCreate(
                [
                    'source' => 'woo',
                    'external_id' => (string) $payload['woo_order_id'],
                ],
                [
                    'source' => 'woo',
                    'external_id' => (string) $payload['woo_order_id'],
                    'customer_id' => $customerId,
                    'woo_order_id' => $payload['woo_order_id'],
                    'status' => $payload['status'] ?? null,
                    'total' => $payload['total'] ?? null,
                    'currency' => $payload['currency'] ?? 'EUR',
                    'issued_at' => $payload['issued_at'] ?? null,
                    'source_updated_at' => $payload['source_updated_at'] ?? null,
                    'meta' => $payload['meta'] ?? null,
                    'source_fingerprint' => $payload['source_fingerprint'],
                ],
            );

            $this->upsertIntegrationLink($invoice, 'woo', (string) $payload['woo_order_id'], null, null, $payload['customer_email'] ?? null, $payload['meta'] ?? null, $payload['source_updated_at'] ?? null);
        }
    }

    public function syncLatePointOrders(?array $rows = null, bool $fullSync = false): void
    {
        $settings = $this->getActiveSettings();

        if (! $settings) {
            return;
        }

        $rows ??= $this->fetchLatePointOrders($settings, $fullSync);

        foreach ($rows as $row) {
            $payload = $this->normalizeLatePointOrderRow($row);

            $customerId = null;

            if (filled($payload['latepoint_customer_id'] ?? null)) {
                $customer = Customer::query()->where('latepoint_customer_id', $payload['latepoint_customer_id'])->first();
                $customerId = $customer?->id;
            }

            $invoice = Invoice::query()->updateOrCreate(
                [
                    'source' => 'latepoint',
                    'external_id' => (string) $payload['latepoint_order_id'],
                ],
                [
                    'source' => 'latepoint',
                    'external_id' => (string) $payload['latepoint_order_id'],
                    'customer_id' => $customerId,
                    'latepoint_order_id' => $payload['latepoint_order_id'],
                    'number' => $payload['confirmation_code'] ?? null,
                    'status' => $payload['payment_status'] ?? null,
                    'total' => $payload['total'] ?? null,
                    'currency' => $payload['currency'] ?? 'EUR',
                    'issued_at' => $payload['issued_at'] ?? null,
                    'source_updated_at' => $payload['source_updated_at'] ?? null,
                    'meta' => $payload['meta'] ?? null,
                    'source_fingerprint' => $payload['source_fingerprint'],
                ],
            );

            $this->upsertIntegrationLink(
                $invoice,
                'latepoint',
                (string) $payload['latepoint_order_id'],
                null,
                $payload['source_url'] ?? null,
                $payload['intent_key'] ?? null,
                $payload['meta'] ?? null,
                $payload['source_updated_at'] ?? null,
            );
        }
    }

    private function fetchLatePointServices(IntegrationSetting $settings, bool $fullSync = false): array
    {
        $connection = $this->getExternalConnectionName($settings);
        $this->applyExternalConnectionRuntimeConfig($connection, $settings);

        $prefix = $settings->external_db_prefix;
        $servicesTable = sprintf('%slatepoint_services', $prefix);
        $since = $this->bookingSyncService->computeSyncSince($settings, $fullSync);

        return DB::connection($connection)
            ->table($servicesTable)
            ->where('updated_at', '>=', $since->toDateTimeString())
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function fetchLatePointCustomers(IntegrationSetting $settings, bool $fullSync = false): array
    {
        $connection = $this->getExternalConnectionName($settings);
        $this->applyExternalConnectionRuntimeConfig($connection, $settings);

        $prefix = $settings->external_db_prefix;
        $customersTable = sprintf('%slatepoint_customers', $prefix);
        $since = $this->bookingSyncService->computeSyncSince($settings, $fullSync);

        return DB::connection($connection)
            ->table($customersTable)
            ->where('updated_at', '>=', $since->toDateTimeString())
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function fetchWooProducts(IntegrationSetting $settings, bool $fullSync = false): array
    {
        $connection = $this->getExternalConnectionName($settings);
        $this->applyExternalConnectionRuntimeConfig($connection, $settings);

        $prefix = $settings->external_db_prefix;
        $postsTable = sprintf('%sposts', $prefix);
        $postMetaTable = sprintf('%spostmeta', $prefix);
        $since = $this->bookingSyncService->computeSyncSince($settings, $fullSync);

        $metaKeys = [
            '_sku',
            '_price',
            '_regular_price',
            '_stock_status',
        ];

        return DB::connection($connection)
            ->table("{$postsTable} as p")
            ->leftJoin("{$postMetaTable} as pm", function ($join) use ($metaKeys): void {
                $join->on('pm.post_id', '=', 'p.ID')->whereIn('pm.meta_key', $metaKeys);
            })
            ->where('p.post_type', 'product')
            ->whereIn('p.post_status', ['publish', 'draft', 'private'])
            ->where('p.post_modified_gmt', '>=', $since->utc()->toDateTimeString())
            ->selectRaw('p.ID as woo_product_id')
            ->selectRaw('p.post_title as name')
            ->selectRaw('p.post_status as status')
            ->selectRaw('p.post_modified_gmt as source_updated_at')
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_sku' THEN pm.meta_value END) as sku")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_price' THEN pm.meta_value END) as price")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_regular_price' THEN pm.meta_value END) as regular_price")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_stock_status' THEN pm.meta_value END) as stock_status")
            ->groupBy(['p.ID', 'p.post_title', 'p.post_status', 'p.post_modified_gmt'])
            ->orderByDesc('p.post_modified_gmt')
            ->limit(500)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function fetchWooCustomerRowsFromOrders(IntegrationSetting $settings, bool $fullSync = false): array
    {
        $connection = $this->getExternalConnectionName($settings);
        $this->applyExternalConnectionRuntimeConfig($connection, $settings);

        $prefix = $settings->external_db_prefix;
        $postsTable = sprintf('%sposts', $prefix);
        $postMetaTable = sprintf('%spostmeta', $prefix);
        $since = $this->bookingSyncService->computeSyncSince($settings, $fullSync);

        $metaKeys = [
            '_billing_first_name',
            '_billing_last_name',
            '_billing_email',
            '_billing_phone',
            '_customer_user',
        ];

        return DB::connection($connection)
            ->table("{$postsTable} as p")
            ->leftJoin("{$postMetaTable} as pm", function ($join) use ($metaKeys): void {
                $join->on('pm.post_id', '=', 'p.ID')->whereIn('pm.meta_key', $metaKeys);
            })
            ->where('p.post_type', 'shop_order')
            ->where('p.post_modified_gmt', '>=', $since->utc()->toDateTimeString())
            ->selectRaw('p.ID as woo_order_id')
            ->selectRaw('p.post_modified_gmt as source_updated_at')
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_first_name' THEN pm.meta_value END) as billing_first_name")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_last_name' THEN pm.meta_value END) as billing_last_name")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_email' THEN pm.meta_value END) as email")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_phone' THEN pm.meta_value END) as phone")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_customer_user' THEN pm.meta_value END) as woo_customer_user_id")
            ->groupBy(['p.ID', 'p.post_modified_gmt'])
            ->orderByDesc('p.post_modified_gmt')
            ->limit(500)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function fetchWooInvoiceRowsFromOrders(IntegrationSetting $settings, bool $fullSync = false): array
    {
        $connection = $this->getExternalConnectionName($settings);
        $this->applyExternalConnectionRuntimeConfig($connection, $settings);

        $prefix = $settings->external_db_prefix;
        $postsTable = sprintf('%sposts', $prefix);
        $postMetaTable = sprintf('%spostmeta', $prefix);
        $since = $this->bookingSyncService->computeSyncSince($settings, $fullSync);

        $metaKeys = [
            '_order_currency',
            '_order_total',
            '_billing_email',
            '_date_paid',
            '_paid_date',
        ];

        return DB::connection($connection)
            ->table("{$postsTable} as p")
            ->leftJoin("{$postMetaTable} as pm", function ($join) use ($metaKeys): void {
                $join->on('pm.post_id', '=', 'p.ID')->whereIn('pm.meta_key', $metaKeys);
            })
            ->where('p.post_type', 'shop_order')
            ->where('p.post_modified_gmt', '>=', $since->utc()->toDateTimeString())
            ->selectRaw('p.ID as woo_order_id')
            ->selectRaw('p.post_status as status')
            ->selectRaw('p.post_date_gmt as created_at_gmt')
            ->selectRaw('p.post_modified_gmt as source_updated_at')
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_order_currency' THEN pm.meta_value END) as currency")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_order_total' THEN pm.meta_value END) as total")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_email' THEN pm.meta_value END) as customer_email")
            ->selectRaw("MAX(CASE WHEN pm.meta_key IN ('_date_paid', '_paid_date') THEN pm.meta_value END) as paid_at_raw")
            ->groupBy(['p.ID', 'p.post_status', 'p.post_date_gmt', 'p.post_modified_gmt'])
            ->orderByDesc('p.post_modified_gmt')
            ->limit(500)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function fetchLatePointOrders(IntegrationSetting $settings, bool $fullSync = false): array
    {
        $connection = $this->getExternalConnectionName($settings);
        $this->applyExternalConnectionRuntimeConfig($connection, $settings);

        $prefix = $settings->external_db_prefix;
        $ordersTable = sprintf('%slatepoint_orders', $prefix);
        $intentsTable = sprintf('%slatepoint_order_intents', $prefix);
        $since = $this->bookingSyncService->computeSyncSince($settings, $fullSync);

        return DB::connection($connection)
            ->table("{$ordersTable} as o")
            ->leftJoin("{$intentsTable} as i", 'i.order_id', '=', 'o.id')
            ->where('o.updated_at', '>=', $since->toDateTimeString())
            ->selectRaw('o.id as latepoint_order_id')
            ->selectRaw('o.customer_id as latepoint_customer_id')
            ->selectRaw('o.total as total')
            ->selectRaw('o.payment_status as payment_status')
            ->selectRaw('o.confirmation_code as confirmation_code')
            ->selectRaw('o.source_url as source_url')
            ->selectRaw('o.updated_at as source_updated_at')
            ->selectRaw('i.intent_key as intent_key')
            ->orderByDesc('o.updated_at')
            ->limit(500)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function normalizeLatePointServiceRow(array $row): array
    {
        $externalId = (string) ($row['id'] ?? '');

        return [
            'source' => 'latepoint',
            'external_id' => $externalId,
            'name' => $row['name'] ?? 'Servicio',
            'status' => $row['status'] ?? null,
            'description' => $row['short_description'] ?? null,
            'price' => $row['charge_amount'] ?? null,
            'currency' => 'EUR',
            'meta' => [
                'raw' => $row,
            ],
            'source_updated_at' => isset($row['updated_at']) ? Carbon::parse($row['updated_at']) : null,
            'source_fingerprint' => sha1(json_encode([
                'source' => 'latepoint',
                'id' => $externalId,
            ])),
        ];
    }

    private function normalizeWooProductRow(array $row): array
    {
        $externalId = (string) ($row['woo_product_id'] ?? '');

        return [
            'source' => 'woo',
            'external_id' => $externalId,
            'name' => $row['name'] ?? 'Producto',
            'status' => $row['status'] ?? null,
            'price' => $row['price'] ?? $row['regular_price'] ?? null,
            'currency' => 'EUR',
            'meta' => [
                'sku' => $row['sku'] ?? null,
                'regular_price' => $row['regular_price'] ?? null,
                'stock_status' => $row['stock_status'] ?? null,
            ],
            'source_updated_at' => isset($row['source_updated_at']) ? Carbon::parse($row['source_updated_at'], 'UTC') : null,
            'source_fingerprint' => sha1(json_encode([
                'source' => 'woo',
                'id' => $externalId,
            ])),
        ];
    }

    private function normalizeLatePointCustomerRow(array $row): array
    {
        $firstName = $row['first_name'] ?? '';
        $lastName = $row['last_name'] ?? '';
        $name = trim(implode(' ', array_filter([(string) $firstName, (string) $lastName])));

        return [
            'name' => filled($name) ? $name : 'Cliente',
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'latepoint_customer_id' => $row['id'] ?? null,
            'wordpress_user_id' => $row['wordpress_user_id'] ?? null,
            'source_updated_at' => isset($row['updated_at']) ? Carbon::parse($row['updated_at']) : null,
            'meta' => [
                'source' => 'latepoint',
            ],
        ];
    }

    private function normalizeWooCustomerRow(array $row): array
    {
        $firstName = $row['billing_first_name'] ?? '';
        $lastName = $row['billing_last_name'] ?? '';
        $name = trim(implode(' ', array_filter([(string) $firstName, (string) $lastName])));

        return [
            'name' => filled($name) ? $name : 'Cliente',
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'woo_customer_user_id' => isset($row['woo_customer_user_id']) && filled($row['woo_customer_user_id']) ? (int) $row['woo_customer_user_id'] : null,
            'source_updated_at' => isset($row['source_updated_at']) ? Carbon::parse($row['source_updated_at'], 'UTC') : null,
            'meta' => [
                'source' => 'woo',
                'woo_order_id' => $row['woo_order_id'] ?? null,
            ],
        ];
    }

    private function normalizeWooInvoiceRow(array $row): array
    {
        $wooOrderId = (int) ($row['woo_order_id'] ?? 0);

        return [
            'woo_order_id' => $wooOrderId,
            'status' => $row['status'] ?? null,
            'total' => $row['total'] ?? null,
            'currency' => $row['currency'] ?? 'EUR',
            'customer_email' => $row['customer_email'] ?? null,
            'issued_at' => $this->normalizeWooPaidAt($row['paid_at_raw'] ?? null) ?? (isset($row['created_at_gmt']) ? Carbon::parse($row['created_at_gmt'], 'UTC') : null),
            'source_updated_at' => isset($row['source_updated_at']) ? Carbon::parse($row['source_updated_at'], 'UTC') : null,
            'meta' => [
                'raw' => $row,
            ],
            'source_fingerprint' => sha1(json_encode([
                'source' => 'woo',
                'order_id' => $wooOrderId,
            ])),
        ];
    }

    private function normalizeLatePointOrderRow(array $row): array
    {
        $orderId = (int) ($row['latepoint_order_id'] ?? 0);

        return [
            'latepoint_order_id' => $orderId,
            'latepoint_customer_id' => $row['latepoint_customer_id'] ?? null,
            'total' => $row['total'] ?? null,
            'currency' => 'EUR',
            'payment_status' => $row['payment_status'] ?? null,
            'confirmation_code' => $row['confirmation_code'] ?? null,
            'source_url' => $row['source_url'] ?? null,
            'intent_key' => $row['intent_key'] ?? null,
            'issued_at' => isset($row['source_updated_at']) ? Carbon::parse($row['source_updated_at']) : null,
            'source_updated_at' => isset($row['source_updated_at']) ? Carbon::parse($row['source_updated_at']) : null,
            'meta' => [
                'raw' => $row,
            ],
            'source_fingerprint' => sha1(json_encode([
                'source' => 'latepoint',
                'order_id' => $orderId,
            ])),
        ];
    }

    private function normalizeWooPaidAt(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function upsertCustomer(array $payload): Customer
    {
        $customer = null;

        if (filled($payload['email'] ?? null)) {
            $customer = Customer::query()->where('email', $payload['email'])->first();
        }

        if (! $customer && filled($payload['latepoint_customer_id'] ?? null)) {
            $customer = Customer::query()->where('latepoint_customer_id', $payload['latepoint_customer_id'])->first();
        }

        $customer ??= new Customer();

        $customer->fill($payload);
        $customer->save();

        return $customer;
    }

    private function upsertIntegrationLink(
        Model $model,
        string $source,
        string $externalId,
        ?string $externalItemId,
        ?string $url,
        ?string $intentKey,
        ?array $meta,
        ?Carbon $sourceUpdatedAt,
    ): void {
        if (blank($externalId)) {
            return;
        }

        IntegrationLink::query()->updateOrCreate(
            [
                'linkable_type' => $model::class,
                'linkable_id' => $model->id,
                'source' => $source,
                'external_id' => $externalId,
                'external_item_id' => $externalItemId,
            ],
            [
                'intent_key' => $intentKey,
                'url' => $url,
                'meta' => $meta,
                'source_updated_at' => $sourceUpdatedAt,
            ],
        );
    }

    private function getActiveSettings(): ?IntegrationSetting
    {
        return IntegrationSetting::query()->active()->first();
    }

    private function getExternalConnectionName(IntegrationSetting $settings): string
    {
        return $settings->external_db_connection ?: 'wordpress_sync';
    }

    private function applyExternalConnectionRuntimeConfig(string $connection, IntegrationSetting $settings): void
    {
        if (! Config::has("database.connections.{$connection}")) {
            $fallback = Config::get('database.connections.wordpress_sync');
            Config::set("database.connections.{$connection}", $fallback);
        }

        Config::set("database.connections.{$connection}.host", $settings->external_db_host);
        Config::set("database.connections.{$connection}.port", $settings->external_db_port);
        Config::set("database.connections.{$connection}.database", $settings->external_db_database);
        Config::set("database.connections.{$connection}.username", $settings->external_db_username);
        Config::set("database.connections.{$connection}.password", $settings->getDecryptedPassword());

        DB::purge($connection);
    }
}
