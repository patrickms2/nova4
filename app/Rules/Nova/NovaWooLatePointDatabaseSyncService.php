<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaExternalBooking;
use App\Models\NovaExternalCatalogItem;
use App\Models\NovaExternalTransaction;
use App\Models\NovaIntegrationSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class NovaWooLatePointDatabaseSyncService
{
    public function __construct(
        private readonly NovaExternalSyncSupport $support,
    ) {}

    public function sync(NovaIntegrationSetting $setting, bool $fullSync = false): array
    {
        $summary = [
            'catalog_processed' => 0,
            'bookings_processed' => 0,
            'customers_processed' => 0,
            'created' => 0,
            'updated' => 0,
        ];

        $this->support->markSyncStarted($setting);

        try {
            $summary = $this->syncWooProducts($setting, $fullSync, $summary);
            $summary = $this->syncTaxilanzRoutes($setting, $fullSync, $summary);
            $summary = $this->syncLatePointServices($setting, $fullSync, $summary);
            $summary = $this->syncWooCustomers($setting, $fullSync, $summary);
            $summary = $this->syncLatePointBookings($setting, $fullSync, $summary);
            $summary = $this->syncWooOrdersAsBookings($setting, $fullSync, $summary);

            $this->support->markSyncFinished($setting);
            $this->support->logSync($setting, 'nova:sync-woo-latepoint', 'mixed', [
                'processed' => $summary['catalog_processed'] + $summary['bookings_processed'] + $summary['customers_processed'],
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'detail' => $summary,
            ]);

            return $summary;
        } catch (\Throwable $exception) {
            $this->support->markSyncFailed($setting, $exception);
            $this->support->logSync($setting, 'nova:sync-woo-latepoint', 'mixed', [
                'processed' => $summary['catalog_processed'] + $summary['bookings_processed'] + $summary['customers_processed'],
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'detail' => $summary,
            ], 'failed', $exception);

            throw $exception;
        }
    }

    private function syncWooProducts(NovaIntegrationSetting $setting, bool $fullSync, array $summary): array
    {
        foreach ($this->fetchWooProductRows($setting, $fullSync) as $row) {
            $payload = $this->normalizeWooProductRow($setting, $row);
            $item = NovaExternalCatalogItem::query()->updateOrCreate(
                [
                    'source' => $payload['source'],
                    'external_id' => $payload['external_id'],
                    'external_item_id' => $payload['external_item_id'],
                ],
                $payload,
            );

            $summary[$item->wasRecentlyCreated ? 'created' : 'updated']++;
            $summary['catalog_processed']++;

            $this->support->upsertIntegrationLink(
                model: $item,
                setting: $setting,
                source: $payload['source'],
                externalId: $payload['external_id'],
                url: $payload['purchase_url'],
                metadata: $payload['metadata'],
                sourceUpdatedAt: $payload['source_updated_at'],
            );
        }

        return $summary;
    }

    private function syncLatePointServices(NovaIntegrationSetting $setting, bool $fullSync, array $summary): array
    {
        foreach ($this->fetchLatePointServiceRows($setting, $fullSync) as $row) {
            $payload = $this->normalizeLatePointServiceRow($setting, $row);
            $item = NovaExternalCatalogItem::query()->updateOrCreate(
                [
                    'source' => $payload['source'],
                    'external_id' => $payload['external_id'],
                    'external_item_id' => $payload['external_item_id'],
                ],
                $payload,
            );

            $summary[$item->wasRecentlyCreated ? 'created' : 'updated']++;
            $summary['catalog_processed']++;

            $this->support->upsertIntegrationLink(
                model: $item,
                setting: $setting,
                source: $payload['source'],
                externalId: $payload['external_id'],
                url: $payload['booking_url'],
                metadata: $payload['metadata'],
                sourceUpdatedAt: $payload['source_updated_at'],
            );
        }

        return $summary;
    }

    private function syncTaxilanzRoutes(NovaIntegrationSetting $setting, bool $fullSync, array $summary): array
    {
        foreach ($this->fetchTaxilanzRouteRows($setting, $fullSync) as $row) {
            $payload = $this->normalizeTaxilanzRouteRow($setting, $row);
            $item = NovaExternalCatalogItem::query()->updateOrCreate(
                [
                    'source' => $payload['source'],
                    'external_id' => $payload['external_id'],
                    'external_item_id' => $payload['external_item_id'],
                ],
                $payload,
            );

            $summary[$item->wasRecentlyCreated ? 'created' : 'updated']++;
            $summary['catalog_processed']++;

            $this->support->upsertIntegrationLink(
                model: $item,
                setting: $setting,
                source: $payload['source'],
                externalId: $payload['external_id'],
                url: $payload['booking_url'] ?? null,
                metadata: $payload['metadata'],
                sourceUpdatedAt: $payload['source_updated_at'],
            );
        }

        return $summary;
    }

    private function syncWooCustomers(NovaIntegrationSetting $setting, bool $fullSync, array $summary): array
    {
        foreach ($this->fetchWooCustomerRows($setting, $fullSync) as $row) {
            $customer = $this->support->upsertCustomer($setting, $this->normalizeWooCustomerRow($row));

            if ($customer) {
                $summary[$customer->wasRecentlyCreated ? 'created' : 'updated']++;
                $summary['customers_processed']++;
            }
        }

        return $summary;
    }

    private function syncLatePointBookings(NovaIntegrationSetting $setting, bool $fullSync, array $summary): array
    {
        foreach ($this->fetchLatePointBookingRows($setting, $fullSync) as $row) {
            $payload = $this->normalizeLatePointBookingRow($setting, $row);
            $booking = $this->upsertBooking($payload);

            $summary[$booking->wasRecentlyCreated ? 'created' : 'updated']++;
            $summary['bookings_processed']++;

            $this->support->upsertIntegrationLink(
                model: $booking,
                setting: $setting,
                source: $payload['source'],
                externalId: (string) $payload['external_id'],
                externalItemId: $payload['external_item_id'],
                url: $payload['admin_url'],
                intentKey: $payload['intent_key'],
                metadata: $payload['metadata'],
                sourceUpdatedAt: $payload['source_updated_at'],
            );
        }

        return $summary;
    }

    private function syncWooOrdersAsBookings(NovaIntegrationSetting $setting, bool $fullSync, array $summary): array
    {
        foreach ($this->fetchWooOrderRows($setting, $fullSync) as $row) {
            $payload = $this->normalizeWooOrderBookingRow($setting, $row);
            $booking = $this->upsertBooking($payload);

            $summary[$booking->wasRecentlyCreated ? 'created' : 'updated']++;
            $summary['bookings_processed']++;

            if ($payload['payment_status'] === 'paid' && filled($payload['total'])) {
                NovaExternalTransaction::query()->updateOrCreate(
                    [
                        'nova_external_booking_id' => $booking->id,
                        'gateway' => 'woo',
                        'gateway_ref' => (string) $payload['external_id'],
                    ],
                    [
                        'nova_business_id' => $setting->nova_business_id,
                        'nova_service_id' => $setting->nova_service_id,
                        'source' => 'woo',
                        'amount' => $payload['total'],
                        'currency' => $payload['currency'],
                        'status' => 'paid',
                        'method' => $row['payment_method'] ?? null,
                        'metadata' => ['raw' => $row],
                    ],
                );
            }

            $this->support->upsertIntegrationLink(
                model: $booking,
                setting: $setting,
                source: $payload['source'],
                externalId: (string) $payload['external_id'],
                externalItemId: $payload['external_item_id'],
                url: $payload['admin_url'],
                intentKey: $payload['intent_key'],
                metadata: $payload['metadata'],
                sourceUpdatedAt: $payload['source_updated_at'],
            );
        }

        return $summary;
    }

    private function upsertBooking(array $payload): NovaExternalBooking
    {
        $booking = null;

        if (filled($payload['intent_key'] ?? null)) {
            $booking = NovaExternalBooking::query()->where('intent_key', $payload['intent_key'])->first();
        }

        if (! $booking && filled($payload['external_id'] ?? null) && filled($payload['external_item_id'] ?? null)) {
            $booking = NovaExternalBooking::query()
                ->where('source', $payload['source'])
                ->where('external_id', $payload['external_id'])
                ->where('external_item_id', $payload['external_item_id'])
                ->first();
        }

        if (! $booking && filled($payload['source_fingerprint'] ?? null)) {
            $booking = NovaExternalBooking::query()->where('source_fingerprint', $payload['source_fingerprint'])->first();
        }

        $booking ??= new NovaExternalBooking;
        $booking->fill($payload);
        $booking->save();

        return $booking;
    }

    private function fetchWooProductRows(NovaIntegrationSetting $setting, bool $fullSync): array
    {
        return $this->safeExternalQuery($setting, function (string $connection, string $prefix, CarbonImmutable $since): array {
            return DB::connection($connection)
                ->table($prefix.'posts as p')
                ->leftJoin($prefix.'postmeta as pm', function ($join): void {
                    $join->on('pm.post_id', '=', 'p.ID')->whereIn('pm.meta_key', ['_sku', '_price', '_regular_price', '_stock_status']);
                })
                ->where('p.post_type', 'product')
                ->whereIn('p.post_status', ['publish', 'draft', 'private'])
                ->where('p.post_modified_gmt', '>=', $since->utc()->toDateTimeString())
                ->selectRaw('p.ID as woo_product_id')
                ->selectRaw('p.post_title as name')
                ->selectRaw('p.post_content as description')
                ->selectRaw('p.post_excerpt as short_description')
                ->selectRaw('p.post_status as status')
                ->selectRaw('p.guid as purchase_url')
                ->selectRaw('p.post_modified_gmt as source_updated_at')
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_sku' THEN pm.meta_value END) as sku")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_price' THEN pm.meta_value END) as price")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_regular_price' THEN pm.meta_value END) as regular_price")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_stock_status' THEN pm.meta_value END) as stock_status")
                ->groupBy(['p.ID', 'p.post_title', 'p.post_content', 'p.post_excerpt', 'p.post_status', 'p.guid', 'p.post_modified_gmt'])
                ->orderByDesc('p.post_modified_gmt')
                ->limit(500)
                ->get()
                ->map(fn ($row): array => (array) $row)
                ->all();
        }, $fullSync);
    }

    private function fetchLatePointServiceRows(NovaIntegrationSetting $setting, bool $fullSync): array
    {
        return $this->safeExternalQuery($setting, function (string $connection, string $prefix, CarbonImmutable $since): array {
            return DB::connection($connection)
                ->table($prefix.'latepoint_services')
                ->where('updated_at', '>=', $since->toDateTimeString())
                ->orderByDesc('updated_at')
                ->limit(500)
                ->get()
                ->map(fn ($row): array => (array) $row)
                ->all();
        }, $fullSync);
    }

    private function fetchTaxilanzRouteRows(NovaIntegrationSetting $setting, bool $fullSync): array
    {
        return $this->safeExternalQuery($setting, function (string $connection, string $prefix, CarbonImmutable $since): array {
            return DB::connection($connection)
                ->table($prefix.'posts as p')
                ->leftJoin($prefix.'postmeta as pm', function ($join): void {
                    $join->on('pm.post_id', '=', 'p.ID')->whereIn('pm.meta_key', ['chbs_vehicle', 'chbs_pickup_hour', '_thumbnail_id']);
                })
                ->whereIn('p.post_type', ['rutas', 'chbs_route'])
                ->where('p.post_modified_gmt', '>=', $since->utc()->toDateTimeString())
                ->selectRaw('p.ID as route_id')
                ->selectRaw('p.post_type as route_type')
                ->selectRaw('p.post_title as name')
                ->selectRaw('p.post_content as description')
                ->selectRaw('p.post_excerpt as short_description')
                ->selectRaw('p.post_status as status')
                ->selectRaw('p.guid as booking_url')
                ->selectRaw('p.post_modified_gmt as source_updated_at')
                ->selectRaw("MAX(CASE WHEN pm.meta_key = 'chbs_vehicle' THEN pm.meta_value END) as chbs_vehicle")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = 'chbs_pickup_hour' THEN pm.meta_value END) as chbs_pickup_hour")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_thumbnail_id' THEN pm.meta_value END) as thumbnail_id")
                ->groupBy(['p.ID', 'p.post_type', 'p.post_title', 'p.post_content', 'p.post_excerpt', 'p.post_status', 'p.guid', 'p.post_modified_gmt'])
                ->orderByDesc('p.post_modified_gmt')
                ->limit(500)
                ->get()
                ->map(fn ($row): array => (array) $row)
                ->all();
        }, $fullSync);
    }

    private function fetchWooCustomerRows(NovaIntegrationSetting $setting, bool $fullSync): array
    {
        return $this->safeExternalQuery($setting, function (string $connection, string $prefix, CarbonImmutable $since): array {
            return DB::connection($connection)
                ->table($prefix.'posts as p')
                ->leftJoin($prefix.'postmeta as pm', function ($join): void {
                    $join->on('pm.post_id', '=', 'p.ID')->whereIn('pm.meta_key', ['_billing_first_name', '_billing_last_name', '_billing_email', '_billing_phone', '_customer_user']);
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
        }, $fullSync);
    }

    private function fetchLatePointBookingRows(NovaIntegrationSetting $setting, bool $fullSync): array
    {
        return $this->safeExternalQuery($setting, function (string $connection, string $prefix, CarbonImmutable $since): array {
            return DB::connection($connection)
                ->table($prefix.'latepoint_bookings as b')
                ->leftJoin($prefix.'latepoint_customers as c', 'c.id', '=', 'b.customer_id')
                ->leftJoin($prefix.'latepoint_services as s', 's.id', '=', 'b.service_id')
                ->where('b.updated_at', '>=', $since->toDateTimeString())
                ->selectRaw('b.id as booking_id')
                ->selectRaw('b.service_id as service_id')
                ->selectRaw('b.customer_id as customer_id')
                ->selectRaw('b.start_date as booking_date')
                ->selectRaw('b.start_time as booking_time')
                ->selectRaw('b.end_time as booking_end_time')
                ->selectRaw('b.status as booking_status')
                ->selectRaw('b.total_attendees as attendees')
                ->selectRaw('b.payment_status as payment_status')
                ->selectRaw('b.updated_at as source_updated_at')
                ->selectRaw('s.name as service_name')
                ->selectRaw('c.first_name as first_name')
                ->selectRaw('c.last_name as last_name')
                ->selectRaw('c.email as email')
                ->selectRaw('c.phone as phone')
                ->orderByDesc('b.updated_at')
                ->limit(500)
                ->get()
                ->map(fn ($row): array => (array) $row)
                ->all();
        }, $fullSync);
    }

    private function fetchWooOrderRows(NovaIntegrationSetting $setting, bool $fullSync): array
    {
        return $this->safeExternalQuery($setting, function (string $connection, string $prefix, CarbonImmutable $since): array {
            return DB::connection($connection)
                ->table($prefix.'posts as p')
                ->leftJoin($prefix.'postmeta as pm', function ($join): void {
                    $join->on('pm.post_id', '=', 'p.ID')->whereIn('pm.meta_key', ['_billing_first_name', '_billing_last_name', '_billing_email', '_billing_phone', '_order_total', '_order_currency', '_payment_method']);
                })
                ->where('p.post_type', 'shop_order')
                ->where('p.post_modified_gmt', '>=', $since->utc()->toDateTimeString())
                ->selectRaw('p.ID as woo_order_id')
                ->selectRaw('p.post_status as woo_order_status')
                ->selectRaw('p.post_date_gmt as ordered_at')
                ->selectRaw('p.post_modified_gmt as source_updated_at')
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_first_name' THEN pm.meta_value END) as billing_first_name")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_last_name' THEN pm.meta_value END) as billing_last_name")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_email' THEN pm.meta_value END) as customer_email")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_phone' THEN pm.meta_value END) as customer_phone")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_order_total' THEN pm.meta_value END) as total")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_order_currency' THEN pm.meta_value END) as currency")
                ->selectRaw("MAX(CASE WHEN pm.meta_key = '_payment_method' THEN pm.meta_value END) as payment_method")
                ->groupBy(['p.ID', 'p.post_status', 'p.post_date_gmt', 'p.post_modified_gmt'])
                ->orderByDesc('p.post_modified_gmt')
                ->limit(500)
                ->get()
                ->map(fn ($row): array => (array) $row)
                ->all();
        }, $fullSync);
    }

    private function safeExternalQuery(NovaIntegrationSetting $setting, callable $query, bool $fullSync): array
    {
        try {
            $connection = $this->support->applyExternalDatabaseConfig($setting);
            $prefix = (string) ($setting->external_db_prefix ?? '');
            $since = $this->support->computeSyncSince($setting, $fullSync);

            return $query($connection, $prefix, $since);
        } catch (\Throwable $exception) {
            Log::warning('Nova external DB sync query skipped', [
                'integration_setting_id' => $setting->id,
                'source_type' => $setting->source_type,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function normalizeWooProductRow(NovaIntegrationSetting $setting, array $row): array
    {
        $externalId = (string) ($row['woo_product_id'] ?? '');

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'nova_integration_setting_id' => $setting->id,
            'source' => 'woo',
            'external_id' => $externalId,
            'external_item_id' => null,
            'type' => 'product',
            'status' => $row['status'] ?? null,
            'name' => $row['name'] ?? 'Producto',
            'description' => $row['description'] ?? null,
            'short_description' => $row['short_description'] ?? null,
            'sku' => $row['sku'] ?? null,
            'price' => $row['price'] ?: null,
            'regular_price' => $row['regular_price'] ?: null,
            'currency' => 'EUR',
            'stock_status' => $row['stock_status'] ?? null,
            'purchase_url' => $row['purchase_url'] ?? null,
            'metadata' => ['raw' => $row],
            'source_updated_at' => isset($row['source_updated_at']) ? CarbonImmutable::parse($row['source_updated_at'], 'UTC') : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'woo', 'id' => $externalId])),
            'last_synced_at' => now(),
        ];
    }

    private function normalizeLatePointServiceRow(NovaIntegrationSetting $setting, array $row): array
    {
        $externalId = (string) ($row['id'] ?? '');

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'nova_integration_setting_id' => $setting->id,
            'source' => 'latepoint',
            'external_id' => $externalId,
            'external_item_id' => null,
            'type' => 'service',
            'status' => $row['status'] ?? null,
            'name' => $row['name'] ?? 'Servicio',
            'description' => $row['short_description'] ?? null,
            'price' => $row['charge_amount'] ?? null,
            'currency' => 'EUR',
            'duration_minutes' => isset($row['duration']) ? (int) $row['duration'] : null,
            'metadata' => ['raw' => $row],
            'source_updated_at' => isset($row['updated_at']) ? CarbonImmutable::parse($row['updated_at']) : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'latepoint', 'id' => $externalId])),
            'last_synced_at' => now(),
        ];
    }

    private function normalizeTaxilanzRouteRow(NovaIntegrationSetting $setting, array $row): array
    {
        $externalId = (string) ($row['route_id'] ?? '');
        $price = $this->extractChbsFixedPrice($row['chbs_vehicle'] ?? null);

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'nova_integration_setting_id' => $setting->id,
            'source' => 'taxilanz_wp',
            'external_id' => $externalId,
            'external_item_id' => $row['route_type'] ?? null,
            'type' => $row['route_type'] === 'chbs_route' ? 'taxi_route' : 'tour_route',
            'status' => $row['status'] ?? null,
            'name' => $row['name'] ?? 'Ruta Taxilanz',
            'description' => $row['description'] ?? null,
            'short_description' => $row['short_description'] ?? null,
            'price' => $price,
            'regular_price' => $price,
            'currency' => 'EUR',
            'booking_url' => $row['booking_url'] ?? null,
            'metadata' => [
                'post_type' => $row['route_type'] ?? null,
                'pickup_hour_raw' => $row['chbs_pickup_hour'] ?? null,
                'chbs_vehicle_raw' => $row['chbs_vehicle'] ?? null,
                'thumbnail_id' => $row['thumbnail_id'] ?? null,
                'raw' => $row,
            ],
            'source_updated_at' => isset($row['source_updated_at']) ? CarbonImmutable::parse($row['source_updated_at'], 'UTC') : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'taxilanz_wp', 'id' => $externalId, 'type' => $row['route_type'] ?? null])),
            'last_synced_at' => now(),
        ];
    }

    private function extractChbsFixedPrice(mixed $serializedVehicle): ?float
    {
        if (blank($serializedVehicle)) {
            return null;
        }

        preg_match_all('/"price_fixed_value";s:\\d+:"([0-9]+(?:\\.[0-9]+)?)"/', (string) $serializedVehicle, $matches);

        if (empty($matches[1])) {
            return null;
        }

        return min(array_map('floatval', $matches[1]));
    }

    private function normalizeWooCustomerRow(array $row): array
    {
        $name = trim(implode(' ', array_filter([(string) ($row['billing_first_name'] ?? ''), (string) ($row['billing_last_name'] ?? '')])));

        return [
            'source' => 'woo',
            'external_id' => filled($row['woo_customer_user_id'] ?? null) ? (string) $row['woo_customer_user_id'] : null,
            'name' => filled($name) ? $name : null,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'metadata' => ['woo_order_id' => $row['woo_order_id'] ?? null],
            'source_updated_at' => isset($row['source_updated_at']) ? CarbonImmutable::parse($row['source_updated_at'], 'UTC') : null,
        ];
    }

    private function normalizeLatePointBookingRow(NovaIntegrationSetting $setting, array $row): array
    {
        $externalId = (string) ($row['booking_id'] ?? '');
        $bookingTime = $this->normalizeMinutesToTime($row['booking_time'] ?? null);
        $bookingDate = $row['booking_date'] ?? null;
        $startsAt = filled($bookingDate) ? CarbonImmutable::parse((string) $bookingDate.' '.$bookingTime) : null;
        $name = trim(implode(' ', array_filter([(string) ($row['first_name'] ?? ''), (string) ($row['last_name'] ?? '')])));

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'source' => 'latepoint',
            'external_id' => $externalId,
            'external_item_id' => isset($row['service_id']) ? (string) $row['service_id'] : null,
            'intent_key' => null,
            'service_name' => $row['service_name'] ?? null,
            'booking_date' => $startsAt?->toDateString(),
            'booking_time' => $startsAt?->format('H:i:s'),
            'booking_starts_at' => $startsAt,
            'attendees' => (int) ($row['attendees'] ?? 1),
            'customer_name' => filled($name) ? $name : null,
            'customer_email' => $row['email'] ?? null,
            'customer_phone' => $row['phone'] ?? null,
            'currency' => 'EUR',
            'booking_status' => $this->mapBookingStatus($row['booking_status'] ?? null),
            'payment_status' => $this->mapPaymentStatus($row['payment_status'] ?? null),
            'metadata' => ['raw' => $row],
            'source_updated_at' => isset($row['source_updated_at']) ? CarbonImmutable::parse($row['source_updated_at']) : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'latepoint', 'booking_id' => $externalId])),
            'last_synced_at' => now(),
        ];
    }

    private function normalizeWooOrderBookingRow(NovaIntegrationSetting $setting, array $row): array
    {
        $externalId = (string) ($row['woo_order_id'] ?? '');
        $name = trim(implode(' ', array_filter([(string) ($row['billing_first_name'] ?? ''), (string) ($row['billing_last_name'] ?? '')])));

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'source' => 'woo',
            'external_id' => $externalId,
            'external_item_id' => null,
            'intent_key' => null,
            'service_name' => 'Pedido WooCommerce',
            'booking_starts_at' => isset($row['ordered_at']) ? CarbonImmutable::parse($row['ordered_at'], 'UTC') : null,
            'attendees' => 1,
            'customer_name' => filled($name) ? $name : null,
            'customer_email' => $row['customer_email'] ?? null,
            'customer_phone' => $row['customer_phone'] ?? null,
            'total' => $row['total'] ?? null,
            'currency' => $row['currency'] ?? 'EUR',
            'booking_status' => 'approved',
            'payment_status' => $this->mapWooPaymentStatus($row['woo_order_status'] ?? null),
            'admin_url' => $this->resolveWooAdminUrl($setting, $externalId),
            'metadata' => ['raw' => $row],
            'source_updated_at' => isset($row['source_updated_at']) ? CarbonImmutable::parse($row['source_updated_at'], 'UTC') : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'woo', 'order_id' => $externalId])),
            'last_synced_at' => now(),
        ];
    }

    private function normalizeMinutesToTime(mixed $value): string
    {
        if (blank($value)) {
            return '09:00:00';
        }

        if (is_numeric($value)) {
            $minutes = max(0, min((int) $value, 24 * 60 - 1));

            return CarbonImmutable::createFromTime(0, 0)->addMinutes($minutes)->format('H:i:s');
        }

        $string = trim((string) $value);

        if (preg_match('/^\d{1,2}:\d{2}/', $string) === 1) {
            return strlen($string) === 5 ? "{$string}:00" : $string;
        }

        return '09:00:00';
    }

    private function mapBookingStatus(?string $status): string
    {
        return match ($status) {
            'approved', 'confirmed' => 'approved',
            'completed' => 'completed',
            'cancelled', 'canceled' => 'cancelled',
            'incident' => 'incident',
            default => 'pending',
        };
    }

    private function mapPaymentStatus(?string $status): string
    {
        return match ($status) {
            'fully_paid', 'paid', 'completed' => 'paid',
            'partial', 'partially_paid' => 'partial',
            'refunded' => 'refunded',
            'mismatch', 'failed' => 'mismatch',
            default => 'unpaid',
        };
    }

    private function mapWooPaymentStatus(?string $status): string
    {
        return match ($status) {
            'wc-completed', 'wc-processing', 'completed', 'processing' => 'paid',
            'wc-refunded', 'refunded' => 'refunded',
            'wc-failed', 'failed' => 'mismatch',
            default => 'unpaid',
        };
    }

    private function resolveWooAdminUrl(NovaIntegrationSetting $setting, string $orderId): ?string
    {
        $path = (string) data_get($setting->settings, 'woocommerce_admin_path', 'wp-admin/post.php?post={id}&action=edit');

        if (blank($setting->base_url) || blank($orderId)) {
            return null;
        }

        return rtrim((string) $setting->base_url, '/').'/'.ltrim(str_replace('{id}', $orderId, $path), '/');
    }
}
