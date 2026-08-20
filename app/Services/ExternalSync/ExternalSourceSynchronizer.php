<?php

namespace App\Services\ExternalSync;

use App\Models\ExternalSource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ExternalSourceSynchronizer
{
    public function __construct(
        private readonly ExternalSyncManager $manager,
    ) {}

    public function sync(ExternalSource $source, bool $fullSync = false): array
    {
        return $this->manager->run(
            $source,
            $fullSync ? 'external-sync:full' : 'external-sync:incremental',
            'mixed',
            fn (): array => match ($source->source_platform) {
                'sirvo' => $this->syncSirvo($source),
                'woo' => $this->syncWoo($source),
                'magento' => $this->syncMagento($source),
                'latepoint' => $this->syncLatePoint($source),
                'mcp' => $this->syncMcp($source),
                default => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 1],
            },
        );
    }

    private function syncMcp(ExternalSource $source): array
    {
        if ($source->resource_type !== 'hotel' || $source->target_model !== 'hotel') {
            return ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 1];
        }

        $summary = $this->emptySummary();
        $seen = [];
        $perPage = 100;

        for ($page = 1; $page <= 100; $page++) {
            $response = $this->genericRequest($source)
                ->get('tools/get_hotels', ['page' => $page, 'per_page' => $perPage])
                ->throw()
                ->json();

            $hotels = $this->recordsFromResponse($response, ['data.tools', 'data.data', 'data.hotels', 'data.users', 'hotels', 'users', 'data', 'items']);

            if ($hotels === []) {
                break;
            }

            foreach ($hotels as $hotel) {
                $name = (string) ($hotel['name'] ?? $hotel['full_name'] ?? $hotel['nombre'] ?? $hotel['hotel'] ?? '');

                if ($name === '') {
                    $summary['skipped']++;

                    continue;
                }

                $externalId = (string) ($hotel['id'] ?? $hotel['hotel_id'] ?? $hotel['uuid'] ?? str($name)->slug());

                if (isset($seen[$externalId])) {
                    continue;
                }

                $seen[$externalId] = true;
                $address = (string) ($hotel['address'] ?? $hotel['direccion'] ?? $hotel['location'] ?? $hotel['direccion_completa'] ?? $name);

                $this->manager->upsertCatalogItem($source, [
                    'external_id' => $externalId,
                    'type' => 'hotel',
                    'status' => (string) ($hotel['status'] ?? 'active'),
                    'name' => $name,
                    'description' => $hotel['description'] ?? $hotel['descripcion'] ?? null,
                    'short_description' => $address,
                    'metadata' => ['raw' => array_merge($hotel, [
                        'name' => $name,
                        'address' => $address,
                        'city' => $hotel['city'] ?? $hotel['municipio'] ?? 'Lanzarote',
                        'country' => $hotel['country'] ?? 'Spain',
                        'latitude' => $hotel['latitude'] ?? $hotel['lat'] ?? null,
                        'longitude' => $hotel['longitude'] ?? $hotel['lng'] ?? $hotel['lon'] ?? null,
                    ])],
                    'source_updated_at' => now(),
                    'source_fingerprint' => sha1(json_encode(['mcp_hotel', $externalId, $hotel])),
                ]);

                $summary['processed']++;
            }

            if (count($hotels) < $perPage) {
                break;
            }
        }

        return $summary;
    }

    private function syncWoo(ExternalSource $source): array
    {
        if (str_starts_with((string) $source->capability, 'chauffeur_')) {
            return $this->syncChauffeur($source);
        }

        $summary = $this->emptySummary();

        if ($this->shouldSyncCatalog($source)) {
            foreach ($this->wooRequest($source)->get('wp-json/wc/v3/products')->throw()->json() ?? [] as $product) {
                $this->manager->upsertCatalogItem($source, [
                    'external_id' => (string) ($product['id'] ?? ''),
                    'type' => $source->target_model === 'tour' ? 'tour' : 'product',
                    'status' => $product['status'] ?? null,
                    'name' => $product['name'] ?? 'WooCommerce product',
                    'description' => $product['description'] ?? null,
                    'short_description' => $product['short_description'] ?? null,
                    'sku' => $product['sku'] ?? null,
                    'price' => $this->nullableDecimal($product['price'] ?? null),
                    'regular_price' => $this->nullableDecimal($product['regular_price'] ?? null),
                    'sale_price' => $this->nullableDecimal($product['sale_price'] ?? null),
                    'currency' => (string) data_get($source->settings, 'currency', 'EUR'),
                    'purchase_url' => $product['permalink'] ?? null,
                    'metadata' => ['raw' => $product],
                    'source_updated_at' => $this->parseDate($product['date_modified_gmt'] ?? null),
                    'source_fingerprint' => sha1(json_encode(['woo_product', $product['id'] ?? null, $product['date_modified_gmt'] ?? null])),
                ]);

                $summary['processed']++;
            }
        }

        if ($this->shouldSyncOrders($source)) {
            foreach ($this->wooRequest($source)->get('wp-json/wc/v3/orders')->throw()->json() ?? [] as $order) {
                $billing = $order['billing'] ?? [];
                $lineItems = $order['line_items'] ?? [];

                if (blank($source->target_model)) {
                    $this->manager->upsertOrder($source, [
                        'external_id' => (string) ($order['id'] ?? ''),
                        'external_increment_id' => (string) ($order['number'] ?? $order['id'] ?? ''),
                        'status' => $order['status'] ?? null,
                        'payment_status' => $this->wooPaymentStatus($order['status'] ?? null),
                        'customer_name' => trim((string) (($billing['first_name'] ?? '').' '.($billing['last_name'] ?? ''))) ?: null,
                        'customer_email' => $billing['email'] ?? null,
                        'grand_total' => $this->nullableDecimal($order['total'] ?? null),
                        'currency' => $order['currency'] ?? 'EUR',
                        'payment_method' => $order['payment_method'] ?? null,
                        'ordered_at' => $this->parseDate($order['date_created_gmt'] ?? null),
                        'items' => $lineItems,
                        'admin_url' => $this->wooAdminUrl($source, (string) ($order['id'] ?? '')),
                        'metadata' => ['raw' => $order],
                        'source_updated_at' => $this->parseDate($order['date_modified_gmt'] ?? null),
                        'source_fingerprint' => sha1(json_encode(['woo_order', $order['id'] ?? null, $order['date_modified_gmt'] ?? null])),
                    ]);
                }

                if ($lineItems !== []) {
                    $this->manager->upsertBooking($source, [
                        'external_id' => (string) ($order['id'] ?? ''),
                        'external_item_id' => (string) data_get($lineItems, '0.id', data_get($lineItems, '0.product_id')),
                        'booking_type' => 'order',
                        'status' => $order['status'] ?? null,
                        'payment_status' => $this->wooPaymentStatus($order['status'] ?? null),
                        'customer_name' => trim((string) (($billing['first_name'] ?? '').' '.($billing['last_name'] ?? ''))) ?: null,
                        'customer_email' => $billing['email'] ?? null,
                        'customer_phone' => $billing['phone'] ?? null,
                        'service_name' => data_get($lineItems, '0.name'),
                        'quantity' => (int) data_get($lineItems, '0.quantity', 1),
                        'total' => $this->nullableDecimal($order['total'] ?? null),
                        'currency' => $order['currency'] ?? 'EUR',
                        'admin_url' => $this->wooAdminUrl($source, (string) ($order['id'] ?? '')),
                        'metadata' => ['raw' => $order],
                        'source_updated_at' => $this->parseDate($order['date_modified_gmt'] ?? null),
                        'source_fingerprint' => sha1(json_encode(['woo_booking', $order['id'] ?? null, data_get($lineItems, '0.id')])),
                    ]);
                }

                $summary['processed']++;
            }
        }

        return $summary;
    }

    private function syncSirvo(ExternalSource $source): array
    {
        return match ($source->target_model) {
            'restaurant_booking' => $this->syncSirvoReservations($source),
            default => $this->syncSirvoBranches($source),
        };
    }

    private function syncSirvoBranches(ExternalSource $source): array
    {
        $summary = $this->emptySummary();
        $branches = $this->recordsFromResponse(
            $this->genericRequest($source)->get('api/branches')->throw()->json(),
            ['data.branches', 'branches', 'data']
        );

        foreach ($branches as $branch) {
            $this->manager->upsertCatalogItem($source, [
                'external_id' => (string) ($branch['id'] ?? $branch['uuid'] ?? $branch['slug'] ?? ''),
                'type' => 'restaurant',
                'status' => (string) ($branch['status'] ?? 'active'),
                'name' => $branch['name'] ?? $branch['restaurant_name'] ?? 'Sirvo restaurant',
                'description' => $branch['description'] ?? null,
                'phone' => $branch['phone'] ?? data_get($branch, 'contact.phone'),
                'email' => $branch['email'] ?? data_get($branch, 'contact.email'),
                'website' => $branch['website'] ?? null,
                'metadata' => ['raw' => $this->withFlatLocation($branch)],
                'source_updated_at' => $this->parseDate($branch['updated_at'] ?? null),
                'source_fingerprint' => sha1(json_encode(['sirvo_branch', $branch['id'] ?? $branch['uuid'] ?? null, $branch['updated_at'] ?? null])),
            ]);

            $summary['processed']++;
        }

        return $summary;
    }

    private function syncSirvoReservations(ExternalSource $source): array
    {
        $summary = $this->emptySummary();
        $reservations = $this->recordsFromResponse(
            $this->genericRequest($source)->get('api/dashboard/reservations')->throw()->json(),
            ['data.reservations', 'reservations', 'data']
        );

        foreach ($reservations as $reservation) {
            $customer = $reservation['customer'] ?? [];

            $this->manager->upsertBooking($source, [
                'external_id' => (string) ($reservation['id'] ?? $reservation['uuid'] ?? ''),
                'booking_type' => 'sirvo',
                'status' => $reservation['status'] ?? null,
                'customer_name' => $reservation['customer_name'] ?? $customer['name'] ?? null,
                'customer_email' => $reservation['customer_email'] ?? $customer['email'] ?? null,
                'customer_phone' => $reservation['customer_phone'] ?? $customer['phone'] ?? null,
                'service_name' => $reservation['branch_name'] ?? data_get($reservation, 'branch.name') ?? 'Reserva restaurante',
                'starts_at' => $this->parseDate($reservation['starts_at'] ?? $reservation['date_time'] ?? $reservation['reservation_at'] ?? null),
                'party_size' => (int) ($reservation['party_size'] ?? $reservation['guests'] ?? 1),
                'metadata' => ['raw' => $reservation],
                'source_updated_at' => $this->parseDate($reservation['updated_at'] ?? null),
                'source_fingerprint' => sha1(json_encode(['sirvo_reservation', $reservation['id'] ?? $reservation['uuid'] ?? null])),
            ]);

            $summary['processed']++;
        }

        return $summary;
    }

    private function syncChauffeur(ExternalSource $source): array
    {
        if ($source->capability === 'chauffeur_routes') {
            return $this->syncChauffeurRoutes($source);
        }

        $summary = $this->emptySummary();
        $path = $source->capability === 'chauffeur_upcoming_bookings'
            ? 'wp-json/taxilanz-mcp/v1/chauffeur/upcoming-bookings'
            : 'wp-json/taxilanz-mcp/v1/chauffeur/bookings';

        $bookings = $this->recordsFromResponse(
            $this->genericRequest($source)->get($path)->throw()->json(),
            ['data.bookings', 'bookings', 'data']
        );

        foreach ($bookings as $booking) {
            if ($source->target_model === 'taxi_service') {
                $this->manager->upsertCatalogItem($source, [
                    'external_id' => (string) ($booking['service_id'] ?? $booking['vehicle_id'] ?? $booking['id'] ?? ''),
                    'type' => 'taxi',
                    'status' => (string) ($booking['status'] ?? 'active'),
                    'name' => $booking['service_name'] ?? $booking['vehicle_name'] ?? $booking['name'] ?? 'Taxi service',
                    'description' => $booking['description'] ?? $this->chauffeurRouteDescription($booking),
                    'phone' => $booking['phone'] ?? $booking['customer_phone'] ?? null,
                    'email' => $booking['email'] ?? null,
                    'metadata' => ['raw' => $this->withFlatLocation($booking)],
                    'source_updated_at' => $this->parseDate($booking['updated_at'] ?? null),
                    'source_fingerprint' => sha1(json_encode(['chauffeur_service', $booking['service_id'] ?? $booking['vehicle_id'] ?? $booking['id'] ?? null])),
                ]);
            } else {
                $this->manager->upsertBooking($source, [
                    'external_id' => (string) ($booking['id'] ?? ''),
                    'booking_type' => 'chauffeur',
                    'status' => (string) ($booking['status'] ?? ''),
                    'customer_name' => $booking['customer_name'] ?? $booking['client_name'] ?? null,
                    'customer_email' => $booking['customer_email'] ?? null,
                    'customer_phone' => $booking['customer_phone'] ?? null,
                    'service_name' => $booking['service_name'] ?? $booking['vehicle_name'] ?? 'Taxi',
                    'starts_at' => $this->parseDate($booking['pickup_datetime'] ?? $booking['starts_at'] ?? null),
                    'quantity' => (int) ($booking['passengers'] ?? $booking['quantity'] ?? 1),
                    'total' => $this->nullableDecimal($booking['total'] ?? $booking['price'] ?? null),
                    'currency' => $booking['currency'] ?? 'EUR',
                    'metadata' => ['raw' => $booking],
                    'source_updated_at' => $this->parseDate($booking['updated_at'] ?? null),
                    'source_fingerprint' => sha1(json_encode(['chauffeur_booking', $booking['id'] ?? null])),
                ]);
            }

            $summary['processed']++;
        }

        return $summary;
    }

    private function syncChauffeurRoutes(ExternalSource $source): array
    {
        $summary = $this->emptySummary();
        $routes = $this->recordsFromResponse(
            $this->genericRequest($source)->get('wp-json/taxilanz-mcp/v1/chauffeur/routes')->throw()->json(),
            ['data.routes', 'routes', 'data']
        );

        foreach ($routes as $route) {
            $this->manager->upsertCatalogItem($source, [
                'external_id' => (string) ($route['id'] ?? $route['route_id'] ?? $route['post_id'] ?? ''),
                'external_item_id' => (string) ($route['post_type'] ?? 'chbs_route'),
                'type' => 'tour',
                'status' => (string) ($route['status'] ?? $route['post_status'] ?? 'publish'),
                'name' => $route['name'] ?? $route['title'] ?? $route['post_title'] ?? $route['route_name'] ?? 'Taxilanz route',
                'description' => $route['description'] ?? $route['post_content'] ?? null,
                'short_description' => $route['short_description'] ?? $route['post_excerpt'] ?? $source->resource_type,
                'price' => $this->nullableDecimal($route['price'] ?? $route['base_price'] ?? $route['fixed_price'] ?? null),
                'regular_price' => $this->nullableDecimal($route['regular_price'] ?? $route['price'] ?? null),
                'currency' => (string) ($route['currency'] ?? data_get($source->settings, 'currency', 'EUR')),
                'duration_hours' => isset($route['duration_hours']) ? (int) $route['duration_hours'] : null,
                'purchase_url' => $route['permalink'] ?? $route['booking_url'] ?? null,
                'metadata' => ['raw' => $this->withFlatLocation($route)],
                'source_updated_at' => $this->parseDate($route['modified_gmt'] ?? $route['source_updated_at'] ?? $route['updated_at'] ?? null),
                'source_fingerprint' => sha1(json_encode(['chauffeur_route', $route['id'] ?? $route['route_id'] ?? $route['post_id'] ?? null, $route['modified_gmt'] ?? $route['updated_at'] ?? null])),
            ]);

            $summary['processed']++;
        }

        return $summary;
    }

    private function syncMagento(ExternalSource $source): array
    {
        $summary = $this->emptySummary();

        foreach ($this->magentoRequest($source)->get('products', ['searchCriteria' => ['currentPage' => 1, 'pageSize' => 200]])->throw()->json('items') ?? [] as $product) {
            $attributes = collect($product['custom_attributes'] ?? [])->pluck('value', 'attribute_code');

            $this->manager->upsertCatalogItem($source, [
                'external_id' => (string) ($product['id'] ?? $product['sku'] ?? ''),
                'type' => 'product',
                'status' => (string) ($product['status'] ?? ''),
                'name' => $product['name'] ?? 'Magento product',
                'description' => $attributes->get('description'),
                'short_description' => $attributes->get('short_description'),
                'sku' => $product['sku'] ?? null,
                'price' => $this->nullableDecimal($product['price'] ?? null),
                'regular_price' => $this->nullableDecimal($product['price'] ?? null),
                'currency' => (string) data_get($source->settings, 'currency', 'EUR'),
                'metadata' => ['raw' => $product],
                'source_updated_at' => $this->parseDate($product['updated_at'] ?? null),
                'source_fingerprint' => sha1(json_encode(['magento_product', $product['id'] ?? null, $product['sku'] ?? null])),
            ]);

            $summary['processed']++;
        }

        foreach ($this->magentoRequest($source)->get('orders', ['searchCriteria' => ['currentPage' => 1, 'pageSize' => 200]])->throw()->json('items') ?? [] as $order) {
            $this->manager->upsertOrder($source, [
                'external_id' => (string) ($order['entity_id'] ?? ''),
                'external_increment_id' => $order['increment_id'] ?? null,
                'status' => $order['status'] ?? null,
                'payment_status' => $this->magentoPaymentStatus($order),
                'customer_name' => trim((string) (($order['customer_firstname'] ?? '').' '.($order['customer_lastname'] ?? ''))) ?: null,
                'customer_email' => $order['customer_email'] ?? null,
                'subtotal' => $this->nullableDecimal($order['subtotal'] ?? null),
                'tax_amount' => $this->nullableDecimal($order['tax_amount'] ?? null),
                'shipping_amount' => $this->nullableDecimal($order['shipping_amount'] ?? null),
                'discount_amount' => abs((float) ($order['discount_amount'] ?? 0)),
                'grand_total' => $this->nullableDecimal($order['grand_total'] ?? null),
                'currency' => $order['order_currency_code'] ?? 'EUR',
                'payment_method' => data_get($order, 'payment.method'),
                'shipping_method' => $order['shipping_description'] ?? null,
                'ordered_at' => $this->parseDate($order['created_at'] ?? null),
                'items' => $order['items'] ?? [],
                'metadata' => ['raw' => $order],
                'source_updated_at' => $this->parseDate($order['updated_at'] ?? null),
                'source_fingerprint' => sha1(json_encode(['magento_order', $order['entity_id'] ?? null])),
            ]);

            $summary['processed']++;
        }

        return $summary;
    }

    private function syncLatePoint(ExternalSource $source): array
    {
        if ($source->capability === 'latepoint_transactions') {
            return $this->syncLatePointTransactions($source);
        }

        if ($source->target_model === 'tour' || $source->resource_type === 'tour_visit' || $source->capability === 'latepoint_services') {
            return $this->syncLatePointServices($source);
        }

        $summary = $this->emptySummary();
        $response = $this->genericRequest($source)
            ->post('wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run', ['input' => []])
            ->throw()
            ->json();

        $bookings = data_get($response, 'data.bookings', data_get($response, 'data', []));

        foreach ($bookings as $booking) {
            $this->manager->upsertBooking($source, [
                'external_id' => (string) ($booking['id'] ?? $booking['booking_id'] ?? ''),
                'booking_type' => 'latepoint',
                'status' => $booking['status'] ?? null,
                'payment_status' => $booking['payment_status'] ?? null,
                'customer_name' => $booking['customer_name'] ?? data_get($booking, 'customer.name'),
                'customer_email' => $booking['customer_email'] ?? data_get($booking, 'customer.email'),
                'customer_phone' => $booking['customer_phone'] ?? data_get($booking, 'customer.phone'),
                'service_name' => $booking['service_name'] ?? data_get($booking, 'service.name'),
                'starts_at' => $this->parseDate($booking['start_datetime'] ?? $booking['starts_at'] ?? null),
                'ends_at' => $this->parseDate($booking['end_datetime'] ?? $booking['ends_at'] ?? null),
                'metadata' => ['raw' => $booking],
                'source_updated_at' => $this->parseDate($booking['updated_at'] ?? null),
                'source_fingerprint' => sha1(json_encode(['latepoint_booking', $booking['id'] ?? $booking['booking_id'] ?? null])),
            ]);

            $summary['processed']++;
        }

        return $summary;
    }

    private function syncLatePointServices(ExternalSource $source): array
    {
        $summary = $this->emptySummary();
        $response = $this->genericRequest($source)
            ->post('wp-json/wp-abilities/v1/abilities/latepoint/get-services/run', ['input' => []])
            ->throw()
            ->json();

        $services = $this->recordsFromResponse($response, ['data.services', 'services', 'data']);
        $defaultLocation = data_get($source->settings, 'sync_target.default_location', []);

        foreach ($services as $service) {
            $durationMinutes = isset($service['duration']) ? (int) $service['duration'] : null;
            $raw = $this->withFlatLocation($service + (is_array($defaultLocation) ? $defaultLocation : []) + [
                'duration_hours' => $durationMinutes ? round($durationMinutes / 60, 2) : null,
            ]);

            $this->manager->upsertCatalogItem($source, [
                'external_id' => (string) ($service['id'] ?? $service['service_id'] ?? ''),
                'type' => 'tour',
                'status' => (string) ($service['status'] ?? 'active'),
                'name' => $service['name'] ?? $service['service_name'] ?? 'LatePoint visit',
                'description' => $service['description'] ?? $service['short_description'] ?? null,
                'short_description' => $service['short_description'] ?? $source->resource_type,
                'price' => $this->nullableDecimal($service['charge_amount'] ?? $service['price'] ?? null),
                'regular_price' => $this->nullableDecimal($service['charge_amount'] ?? $service['price'] ?? null),
                'currency' => (string) ($service['currency'] ?? data_get($source->settings, 'currency', 'EUR')),
                'metadata' => ['raw' => $raw],
                'source_updated_at' => $this->parseDate($service['updated_at'] ?? null),
                'source_fingerprint' => sha1(json_encode(['latepoint_service', $service['id'] ?? $service['service_id'] ?? null, $service['updated_at'] ?? null])),
            ]);

            $summary['processed']++;
        }

        return $summary;
    }

    private function syncLatePointTransactions(ExternalSource $source): array
    {
        $summary = $this->emptySummary();

        $response = $this->genericRequest($source)
            ->post('wp-json/wp-abilities/v1/abilities/latepoint/list-transactions/run', ['input' => []])
            ->throw()
            ->json();

        $transactions = data_get($response, 'data.transactions')
            ?? data_get($response, 'transactions')
            ?? data_get($response, 'data')
            ?? [];
        $transactions = is_array($transactions) ? $transactions : [];

        foreach ($transactions as $t) {
            $booking = $t['booking'] ?? null;

            $sourceUpdatedAt = $this->parseDate($t['updated_at'] ?? null);
            $paidAt = $this->parseDate($t['created_at'] ?? null);
            $serviceName = is_array($booking) ? ($booking['service_name'] ?? null) : null;

            $this->manager->upsertPayment($source, [
                'external_id' => (string) ($t['id'] ?? ''),
                'external_token' => $t['token'] ?? null,
                'external_receipt_number' => $t['receipt_number'] ?? null,
                'external_order_id' => isset($t['order_id']) ? (string) $t['order_id'] : null,
                'external_booking_id' => is_array($booking) ? (isset($booking['id']) ? (string) $booking['id'] : null) : null,
                'external_service_id' => is_array($booking) ? (isset($booking['service_id']) ? (string) $booking['service_id'] : null) : null,
                // For La Geria we treat transactions as tour booking payments; keep it explicit per source.
                'resource_type' => $source->resource_type ?: 'tour_booking',
                'target_model' => $source->target_model ?: 'tour_booking',
                'customer_name' => $t['customer_name'] ?? null,
                'customer_email' => $t['customer_email'] ?? null,
                'service_name' => $serviceName,
                'processor' => $t['processor'] ?? null,
                'payment_method' => $t['payment_method'] ?? null,
                'kind' => $t['kind'] ?? null,
                'status' => $t['status'] ?? null,
                'amount' => $this->nullableDecimal($t['amount'] ?? null),
                'currency' => (string) data_get($source->settings, 'currency', 'EUR'),
                'paid_at' => $paidAt,
                'metadata' => ['raw' => $t],
                'source_updated_at' => $sourceUpdatedAt,
                'source_fingerprint' => sha1(json_encode(['latepoint_transaction', $t['id'] ?? null, $t['updated_at'] ?? null])),
            ]);

            $summary['processed']++;
        }

        return $summary;
    }

    private function shouldSyncCatalog(ExternalSource $source): bool
    {
        if (blank($source->target_model)) {
            return true;
        }

        return ! str_ends_with((string) $source->target_model, '_booking');
    }

    private function shouldSyncOrders(ExternalSource $source): bool
    {
        if (blank($source->target_model)) {
            return true;
        }

        return str_ends_with((string) $source->target_model, '_booking');
    }

    /**
     * @param  list<string>  $paths
     * @return list<array<string, mixed>>
     */
    private function recordsFromResponse(mixed $response, array $paths): array
    {
        foreach ($paths as $path) {
            $records = data_get($response, $path);

            if (is_array($records)) {
                return array_is_list($records) ? $records : [$records];
            }
        }

        return is_array($response) && array_is_list($response) ? $response : [];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function withFlatLocation(array $record): array
    {
        return $record + [
            'latitude' => $record['latitude'] ?? data_get($record, 'location.latitude') ?? data_get($record, 'coordinates.lat'),
            'longitude' => $record['longitude'] ?? data_get($record, 'location.longitude') ?? data_get($record, 'coordinates.lng'),
            'address' => $record['address'] ?? data_get($record, 'location.address') ?? $record['pickup_location'] ?? null,
            'city' => $record['city'] ?? data_get($record, 'location.city') ?? null,
            'country' => $record['country'] ?? data_get($record, 'location.country') ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $booking
     */
    private function chauffeurRouteDescription(array $booking): ?string
    {
        $pickup = $booking['pickup_location'] ?? null;
        $dropoff = $booking['dropoff_location'] ?? null;

        if (blank($pickup) && blank($dropoff)) {
            return null;
        }

        return trim((string) $pickup.' -> '.(string) $dropoff);
    }

    private function wooRequest(ExternalSource $source): PendingRequest
    {
        $request = $this->genericRequest($source);
        $credentials = $source->credentials ?? [];

        if (! empty($credentials['consumer_key']) && ! empty($credentials['consumer_secret'])) {
            return $request->withBasicAuth((string) $credentials['consumer_key'], (string) $credentials['consumer_secret']);
        }

        return $request;
    }

    private function magentoRequest(ExternalSource $source): PendingRequest
    {
        $baseUrl = rtrim((string) ($source->api_url ?: $source->base_url), '/');

        if (! str_contains($baseUrl, '/rest/')) {
            $baseUrl .= '/rest/V1';
        }

        $request = Http::baseUrl($baseUrl)->acceptJson()->timeout(30);
        $token = data_get($source->credentials, 'access_token');
        $token = '22vu69f2c19804n6cigir5uhsnbwss3z';

        return $token ? $request->withToken((string) $token) : $request;
    }

    private function genericRequest(ExternalSource $source): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) ($source->api_url ?: $source->base_url), '/'))
            ->acceptJson()
            ->timeout(30);

        $host = parse_url((string) ($source->api_url ?: $source->base_url), PHP_URL_HOST);
        if (is_string($host) && str_ends_with($host, '.test')) {
            $request = $request->withoutVerifying();
        }

        $token = $this->bearerTokenFor($source);

        if (! empty($token)) {
            $request = $request->withToken((string) $token);
        }

        $localHeader = data_get($source->settings, 'local_header')
            ?: data_get($source->server?->metadata, 'local_header');

        $localHeaderName = data_get($localHeader, 'name');
        $localHeaderValue = data_get($localHeader, 'value')
            ?: $this->envValue(data_get($localHeader, 'env'));

        if (! empty($localHeaderName) && ! empty($localHeaderValue)) {
            $request = $request->withHeaders([(string) $localHeaderName => (string) $localHeaderValue]);
        }

        return $request;
    }

    private function bearerTokenFor(ExternalSource $source): ?string
    {
        $token = data_get($source->credentials, 'access_token')
            ?: $this->envValue(data_get($source->settings, 'auth_token_env'))
            ?: $this->envValue(data_get($source->server?->metadata, 'auth_token_env'));

        if (! blank($token)) {
            return (string) $token;
        }

        return $this->loginTokenFor($source);
    }

    private function loginTokenFor(ExternalSource $source): ?string
    {
        $login = data_get($source->settings, 'login')
            ?: data_get($source->server?->metadata, 'login');

        $path = data_get($login, 'path');
        $user = $this->envValue(data_get($login, 'user_env'));
        $password = $this->envValue(data_get($login, 'password_env'));

        if (blank($path) || blank($user) || blank($password)) {
            return null;
        }

        $response = Http::baseUrl(rtrim((string) ($source->api_url ?: $source->base_url), '/'))
            ->acceptJson()
            ->timeout(30)
            ->post((string) $path, [
                'email' => $user,
                'username' => $user,
                'password' => $password,
            ])
            ->throw()
            ->json();

        $token = data_get($response, 'session.access_token')
            ?: data_get($response, 'session.token')
            ?: data_get($response, 'session.accessToken')
            ?: data_get($response, 'data.access_token')
            ?: data_get($response, 'data.token')
            ?: data_get($response, 'access_token')
            ?: data_get($response, 'token');

        return blank($token) ? null : (string) $token;
    }

    private function envValue(mixed $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        $value = env((string) $name);

        return blank($value) ? null : (string) $value;
    }

    private function emptySummary(): array
    {
        return ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0];
    }

    private function nullableDecimal(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        return blank($value) ? null : CarbonImmutable::parse($value);
    }

    private function wooPaymentStatus(?string $status): string
    {
        return in_array($status, ['processing', 'completed'], true) ? 'paid' : 'pending';
    }

    private function magentoPaymentStatus(array $order): string
    {
        $grandTotal = (float) ($order['grand_total'] ?? 0);
        $totalPaid = (float) ($order['total_paid'] ?? 0);

        return ($totalPaid >= $grandTotal && $grandTotal > 0) || ($order['status'] ?? null) === 'complete'
            ? 'paid'
            : 'pending';
    }

    private function wooAdminUrl(ExternalSource $source, string $orderId): ?string
    {
        if ($orderId === '' || blank($source->base_url)) {
            return null;
        }

        return rtrim((string) $source->base_url, '/').'/wp-admin/post.php?post='.$orderId.'&action=edit';
    }
}
