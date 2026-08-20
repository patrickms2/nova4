<?php

namespace App\Services\Bookings;

use App\Models\IntegrationSetting;
use App\Models\Reservation;
use App\Models\ReservationSync;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingSyncService
{
    public function computeSyncSince(IntegrationSetting $settings, bool $fullSync = false): Carbon
    {
        if ($fullSync) {
            return Carbon::create(1970, 1, 1, 0, 0, 0, 'UTC');
        }

        $windowHours = (int) ($settings->sync_window_hours ?: 24);
        $windowHours = max(1, min($windowHours, 24 * 14));

        $fallback = now()->subHours($windowHours);

        if (filled($settings->last_sync_started_at)) {
            return Carbon::parse($settings->last_sync_started_at)->subMinutes(5);
        }

        if (filled($settings->last_sync_finished_at)) {
            return Carbon::parse($settings->last_sync_finished_at)->subMinutes(5);
        }

        return $fallback;
    }

    public function syncLatePointReservations(bool $fullSync = false): void
    {
        $this->syncFromLatePoint(null, $fullSync);
    }

    public function syncWooOrders(bool $fullSync = false): void
    {
        $this->syncFromWoo(null, $fullSync);
    }

    public function syncWooPays(bool $fullSync = false): void
    {
        $settings = $this->getActiveSettings();

        if (! $settings || ! $settings->sync_woocommerce_enabled) {
            return;
        }

        $this->markSyncStarted($settings);

        try {
            $rows = $this->fetchWooRows($settings, $fullSync);

            foreach ($rows as $row) {
                $payload = $this->normalizeWooRow($row, $settings);
                $reservation = $this->persistRow('woo', $payload);

                $this->syncWooTransaction($reservation, $payload, $row);
                $this->reconcilePaymentState($reservation);
            }

            $this->markSyncFinished($settings);
        } catch (\Throwable $exception) {
            $this->markSyncFailed($settings, $exception);
            throw $exception;
        }
    }

    public function syncFromLatePoint(?array $rows = null, bool $fullSync = false): void
    {
        $settings = $this->getActiveSettings();

        if (! $settings || ! $settings->sync_latepoint_enabled) {
            return;
        }

        $this->markSyncStarted($settings);

        try {
            $rows ??= $this->fetchLatePointRows($settings, $fullSync);

            foreach ($rows as $row) {
                $this->persistRow('latepoint', $this->normalizeLatePointRow($row, $settings));
            }

            $this->markSyncFinished($settings);
        } catch (\Throwable $exception) {
            $this->markSyncFailed($settings, $exception);
            throw $exception;
        }
    }

    public function syncFromWoo(?array $rows = null, bool $fullSync = false): void
    {
        $settings = $this->getActiveSettings();

        if (! $settings || ! $settings->sync_woocommerce_enabled) {
            return;
        }

        $this->markSyncStarted($settings);

        try {
            $rows ??= $this->fetchWooRows($settings, $fullSync);

            foreach ($rows as $row) {
                $this->persistRow('woo', $this->normalizeWooRow($row, $settings));
            }

            $this->markSyncFinished($settings);
        } catch (\Throwable $exception) {
            $this->markSyncFailed($settings, $exception);
            throw $exception;
        }
    }

    private function fetchLatePointRows(IntegrationSetting $settings, bool $fullSync = false): array
    {
        $connection = $this->getExternalConnectionName($settings);
        $this->applyExternalConnectionRuntimeConfig($connection, $settings);

        $prefix = $settings->external_db_prefix;
        $bookingsTable = sprintf('%slatepoint_bookings', $prefix);
        $customersTable = sprintf('%slatepoint_customers', $prefix);
        $ordersTable = sprintf('%slatepoint_orders', $prefix);
        $orderItemsTable = sprintf('%slatepoint_order_items', $prefix);
        $orderIntentsTable = sprintf('%slatepoint_order_intents', $prefix);
        $servicesTable = sprintf('%slatepoint_services', $prefix);
        $agentsTable = sprintf('%slatepoint_agents', $prefix);
        $since = $this->computeSyncSince($settings, $fullSync);

        return DB::connection($connection)
            ->table("{$bookingsTable} as b")
            ->leftJoin("{$customersTable} as c", 'c.id', '=', 'b.customer_id')
            ->leftJoin("{$orderItemsTable} as oi", 'oi.id', '=', 'b.order_item_id')
            ->leftJoin("{$ordersTable} as o", 'o.id', '=', 'oi.order_id')
            ->leftJoin("{$orderIntentsTable} as i", 'i.order_id', '=', 'oi.order_id')
            ->leftJoin("{$servicesTable} as s", 's.id', '=', 'b.service_id')
            ->leftJoin("{$agentsTable} as a", 'a.id', '=', 'b.agent_id')
            ->where('b.updated_at', '>=', $since->toDateTimeString())
            ->selectRaw('b.id as latepoint_booking_id')
            ->selectRaw('oi.order_id as latepoint_order_id')
            ->selectRaw('b.order_item_id as latepoint_order_item_id')
            ->selectRaw('b.start_datetime_utc as booking_starts_at')
            ->selectRaw('b.end_datetime_utc as booking_ends_at')
            ->selectRaw('b.service_id as service_id')
            ->selectRaw('s.name as service_name')
            ->selectRaw('b.agent_id as agent_id')
            ->selectRaw("TRIM(COALESCE(a.display_name, CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, '')))) as agent_name")
            ->selectRaw('COALESCE(b.total_attendees, b.total_attendies) as attendees')
            ->selectRaw('b.status as booking_status')
            ->selectRaw('COALESCE(oi.total, b.price) as total')
            ->selectRaw('COALESCE(o.payment_status, b.payment_status) as payment_status')
            ->selectRaw('COALESCE(o.confirmation_code, b.booking_code) as confirmation_code')
            ->selectRaw('i.intent_key as intent_key')
            ->selectRaw('b.updated_at as source_updated_at')
            ->selectRaw("CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')) as customer_name")
            ->selectRaw('c.email as customer_email')
            ->selectRaw('c.phone as customer_phone')
            ->orderByDesc('b.updated_at')
            ->limit(500)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function fetchWooRows(IntegrationSetting $settings, bool $fullSync = false): array
    {
        $connection = $this->getExternalConnectionName($settings);
        $this->applyExternalConnectionRuntimeConfig($connection, $settings);

        $prefix = $settings->external_db_prefix;
        $postsTable = sprintf('%sposts', $prefix);
        $postMetaTable = sprintf('%spostmeta', $prefix);
        $orderItemsTable = sprintf('%swoocommerce_order_items', $prefix);
        $orderItemMetaTable = sprintf('%swoocommerce_order_itemmeta', $prefix);
        $since = $this->computeSyncSince($settings, $fullSync);

        $postMetaKeys = [
            '_billing_first_name',
            '_billing_last_name',
            '_billing_email',
            '_billing_phone',
            '_order_currency',
            '_order_total',
            '_payment_method_title',
            '_date_paid',
            '_paid_date',
            '_transaction_id',
        ];

        $orderItemMetaKeys = [
            '_qty',
            '_line_total',
            '_lageria_lang',
            'Booking Date',
            'Booking Time',
            'Attendees',
            'Adults',
            'Children',
            'latepoint_order_intent_key',
            'latepoint_service_id',
        ];

        return DB::connection($connection)
            ->table("{$postsTable} as p")
            ->join("{$orderItemsTable} as oi", function ($join): void {
                $join->on('oi.order_id', '=', 'p.ID')
                    ->where('oi.order_item_type', '=', 'line_item');
            })
            ->leftJoin("{$postMetaTable} as pm", function ($join) use ($postMetaKeys): void {
                $join->on('pm.post_id', '=', 'p.ID')
                    ->whereIn('pm.meta_key', $postMetaKeys);
            })
            ->leftJoin("{$orderItemMetaTable} as oim", function ($join) use ($orderItemMetaKeys): void {
                $join->on('oim.order_item_id', '=', 'oi.order_item_id')
                    ->whereIn('oim.meta_key', $orderItemMetaKeys);
            })
            ->where('p.post_type', 'shop_order')
            ->whereIn('p.post_status', ['wc-completed', 'wc-processing', 'wc-pending', 'wc-on-hold', 'wc-failed', 'wc-refunded'])
            ->where('p.post_modified_gmt', '>=', $since->utc()->toDateTimeString())
            ->selectRaw('p.ID as woo_order_id')
            ->selectRaw('oi.order_item_id as woo_order_item_id')
            ->selectRaw('oi.order_item_name as service_name')
            ->selectRaw('p.post_status as woo_order_status')
            ->selectRaw('p.post_modified_gmt as source_updated_at')
            ->selectRaw('p.post_date_gmt as booking_starts_at')
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_first_name' THEN pm.meta_value END) as billing_first_name")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_last_name' THEN pm.meta_value END) as billing_last_name")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_email' THEN pm.meta_value END) as customer_email")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_billing_phone' THEN pm.meta_value END) as customer_phone")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_order_currency' THEN pm.meta_value END) as currency")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_order_total' THEN pm.meta_value END) as order_total")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_payment_method_title' THEN pm.meta_value END) as payment_method")
            ->selectRaw("MAX(CASE WHEN pm.meta_key IN ('_date_paid', '_paid_date') THEN pm.meta_value END) as paid_at_raw")
            ->selectRaw("MAX(CASE WHEN pm.meta_key = '_transaction_id' THEN pm.meta_value END) as transaction_ref")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = '_qty' THEN oim.meta_value END) as qty")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = '_line_total' THEN oim.meta_value END) as line_total")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = '_lageria_lang' THEN oim.meta_value END) as language_code")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = 'Booking Date' THEN oim.meta_value END) as booking_date")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = 'Booking Time' THEN oim.meta_value END) as booking_time")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = 'Attendees' THEN oim.meta_value END) as attendees")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = 'Adults' THEN oim.meta_value END) as adults")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = 'Children' THEN oim.meta_value END) as children")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = 'latepoint_order_intent_key' THEN oim.meta_value END) as intent_key")
            ->selectRaw("MAX(CASE WHEN oim.meta_key = 'latepoint_service_id' THEN oim.meta_value END) as service_id")
            ->groupBy([
                'p.ID',
                'oi.order_item_id',
                'oi.order_item_name',
                'p.post_status',
                'p.post_modified_gmt',
                'p.post_date_gmt',
            ])
            ->orderByDesc('p.post_modified_gmt')
            ->limit(500)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
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

    public function reconcilePaymentState(Reservation $reservation): Reservation
    {
        $transactionAmount = (float) $reservation->transactions()->sum('amount');
        $bookingTotal = (float) ($reservation->total ?? 0);

        if ($transactionAmount <= 0) {
            $reservation->payment_status = 'unpaid';
        } elseif ($transactionAmount < $bookingTotal) {
            $reservation->payment_status = 'partial';
        } elseif ($transactionAmount > $bookingTotal && $bookingTotal > 0) {
            $reservation->payment_status = 'mismatch';
        } else {
            $reservation->payment_status = 'paid';
        }

        $reservation->save();

        return $reservation;
    }

    private function persistRow(string $source, array $payload): Reservation
    {
        try {
            $reservation = $this->upsertReservation($payload);

            $reservation->sync_status = 'ok';
            $reservation->synced_at = now();
            $reservation->save();

            ReservationSync::query()->create([
                'reservation_id' => $reservation->id,
                'source' => $source,
                'job_name' => 'sync',
                'payload_hash' => $payload['source_fingerprint'],
                'status' => 'ok',
                'processed_at' => now(),
            ]);

            return $reservation;
        } catch (\Throwable $exception) {
            Log::error('Reservation sync failed', [
                'source' => $source,
                'message' => $exception->getMessage(),
                'payload' => Arr::except($payload, ['internal_notes']),
            ]);

            ReservationSync::query()->create([
                'source' => $source,
                'job_name' => 'sync',
                'payload_hash' => $payload['source_fingerprint'] ?? null,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'meta' => ['payload' => Arr::except($payload, ['internal_notes'])],
                'processed_at' => now(),
            ]);

            throw $exception;
        }
    }

    private function upsertReservation(array $payload): Reservation
    {
        $reservation = $this->findReservationForPayload($payload) ?? new Reservation;

        foreach ($payload as $key => $value) {
            if (! $this->isPresent($value)) {
                continue;
            }

            if ($key === 'source_fingerprint' && filled($reservation->source_fingerprint)) {
                continue;
            }

            $reservation->{$key} = $value;
        }

        if (blank($reservation->booking_starts_at) && filled($reservation->booking_date) && filled($reservation->booking_time)) {
            $reservation->booking_starts_at = Carbon::parse($reservation->booking_date->toDateString().' '.$reservation->booking_time);
        }

        if ($reservation->exists) {
            if (filled($payload['source_updated_at'] ?? null)) {
                $incoming = Carbon::parse($payload['source_updated_at']);
                $reservation->source_updated_at = $reservation->source_updated_at?->greaterThan($incoming) === true
                    ? $reservation->source_updated_at
                    : $incoming;
            }
        }

        $reservation->save();

        return $reservation;
    }

    private function findReservationForPayload(array $payload): ?Reservation
    {
        if (filled($payload['latepoint_booking_id'] ?? null)) {
            return Reservation::query()->where('latepoint_booking_id', $payload['latepoint_booking_id'])->first();
        }

        if (filled($payload['intent_key'] ?? null)) {
            return Reservation::query()->where('intent_key', $payload['intent_key'])->first();
        }

        if (filled($payload['latepoint_order_item_id'] ?? null)) {
            return Reservation::query()->where('latepoint_order_item_id', $payload['latepoint_order_item_id'])->first();
        }

        if (filled($payload['woo_order_id'] ?? null) && filled($payload['woo_order_item_id'] ?? null)) {
            return Reservation::query()
                ->where('woo_order_id', $payload['woo_order_id'])
                ->where('woo_order_item_id', $payload['woo_order_item_id'])
                ->first();
        }

        if (filled($payload['woo_order_id'] ?? null)) {
            return Reservation::query()->where('woo_order_id', $payload['woo_order_id'])->first();
        }

        if (filled($payload['source_fingerprint'] ?? null)) {
            return Reservation::query()->where('source_fingerprint', $payload['source_fingerprint'])->first();
        }

        return null;
    }

    private function isPresent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }

    private function normalizeLatePointRow(array $row, IntegrationSetting $settings): array
    {
        $bookingStartsAtUtc = isset($row['booking_starts_at']) ? Carbon::parse($row['booking_starts_at'], 'UTC') : null;
        $bookingStartsAt = $bookingStartsAtUtc?->setTimezone(config('app.timezone')) ?? now();

        $bookingDate = Carbon::parse($bookingStartsAt->toDateString());
        $bookingTime = $bookingStartsAt->format('H:i:s');
        $bookingEndsAtUtc = isset($row['booking_ends_at']) ? Carbon::parse($row['booking_ends_at'], 'UTC') : null;
        $bookingEndsAt = $bookingEndsAtUtc?->setTimezone(config('app.timezone'));

        $latepointBookingId = $row['latepoint_booking_id'] ?? null;
        $latepointOrderId = $row['latepoint_order_id'] ?? null;
        $latepointOrderItemId = $row['latepoint_order_item_id'] ?? null;

        return [
            'latepoint_booking_id' => $row['latepoint_booking_id'] ?? null,
            'latepoint_order_id' => $latepointOrderId,
            'latepoint_order_item_id' => $latepointOrderItemId,
            'latepoint_transaction_id' => $row['latepoint_transaction_id'] ?? null,
            'intent_key' => $row['intent_key'] ?? null,
            'service_id' => (string) ($row['service_id'] ?? ''),
            'service_name' => $row['service_name'] ?? 'Servicio sin nombre',
            'agent_id' => (string) ($row['agent_id'] ?? ''),
            'agent_name' => $row['agent_name'] ?? null,
            'language_code' => $row['language_code'] ?? null,
            'booking_date' => $bookingDate->toDateString(),
            'booking_time' => $bookingTime,
            'booking_starts_at' => $bookingStartsAt,
            'booking_ends_at' => $bookingEndsAt,
            'attendees' => (int) ($row['attendees'] ?? 1),
            'adults' => (int) ($row['adults'] ?? 0),
            'children' => (int) ($row['children'] ?? 0),
            'customer_name' => $row['customer_name'] ?? 'Cliente',
            'customer_email' => $row['customer_email'] ?? null,
            'customer_phone' => $row['customer_phone'] ?? null,
            'total' => $row['total'] ?? null,
            'currency' => $row['currency'] ?? 'EUR',
            'booking_status' => $this->mapBookingStatus($row['booking_status'] ?? null),
            'payment_status' => $this->mapPaymentStatus($row['payment_status'] ?? null),
            'confirmation_code' => $row['confirmation_code'] ?? null,
            'invoice_url' => $row['invoice_url'] ?? null,
            'latepoint_admin_url' => $this->resolveLatePointAdminUrl($settings, $latepointBookingId),
            'source_updated_at' => isset($row['source_updated_at']) ? Carbon::parse($row['source_updated_at']) : null,
            'source_fingerprint' => sha1(json_encode([
                'source' => 'latepoint',
                'latepoint_booking_id' => $row['latepoint_booking_id'] ?? null,
                'latepoint_order_item_id' => $row['latepoint_order_item_id'] ?? null,
                'booking_date' => $bookingDate->toDateString(),
                'booking_time' => $bookingTime,
                'service_id' => $row['service_id'] ?? null,
            ])),
        ];
    }

    private function normalizeWooRow(array $row, ?IntegrationSetting $settings): array
    {
        $wooOrderId = $row['woo_order_id'] ?? null;
        $wooOrderItemId = $row['woo_order_item_id'] ?? null;
        $billingFirstName = $row['billing_first_name'] ?? null;
        $billingLastName = $row['billing_last_name'] ?? null;
        $customerName = trim(implode(' ', array_filter([(string) $billingFirstName, (string) $billingLastName])));

        $bookingDate = $row['booking_date'] ?? null;
        $bookingTime = $this->normalizeMinutesToTime($row['booking_time'] ?? null);
        $bookingStartsAt = filled($bookingDate)
            ? Carbon::parse((string) $bookingDate.' '.$bookingTime)
            : (isset($row['booking_starts_at']) ? Carbon::parse($row['booking_starts_at']) : now());

        $attendees = (int) ($row['attendees'] ?? $row['qty'] ?? 1);

        return [
            'woo_order_id' => $wooOrderId,
            'woo_order_item_id' => $wooOrderItemId,
            'intent_key' => $row['intent_key'] ?? null,
            'service_id' => isset($row['service_id']) ? (string) $row['service_id'] : null,
            'service_name' => $row['service_name'] ?? 'Servicio sin nombre',
            'customer_name' => filled($customerName) ? $customerName : 'Cliente',
            'customer_email' => $row['customer_email'] ?? null,
            'customer_phone' => $row['customer_phone'] ?? null,
            'booking_date' => $bookingStartsAt->toDateString(),
            'booking_time' => $bookingStartsAt->format('H:i:s'),
            'booking_starts_at' => $bookingStartsAt,
            'attendees' => $attendees > 0 ? $attendees : 1,
            'adults' => (int) ($row['adults'] ?? 0),
            'children' => (int) ($row['children'] ?? 0),
            'total' => $row['line_total'] ?? $row['order_total'] ?? null,
            'currency' => $row['currency'] ?? 'EUR',
            'booking_status' => $this->mapBookingStatus($row['booking_status'] ?? null),
            'payment_status' => $this->mapWooPaymentStatus($row['woo_order_status'] ?? null),
            'confirmation_code' => $row['confirmation_code'] ?? null,
            'woo_admin_url' => $settings ? $this->resolveWooAdminUrl($settings, $wooOrderId) : null,
            'latepoint_admin_url' => $row['latepoint_admin_url'] ?? null,
            'source_updated_at' => isset($row['source_updated_at']) ? Carbon::parse($row['source_updated_at']) : null,
            'language_code' => isset($row['language_code']) ? strtoupper(substr((string) $row['language_code'], 0, 3)) : null,
            'source_fingerprint' => sha1(json_encode([
                'source' => 'woo',
                'woo_order_id' => $wooOrderId,
                'woo_order_item_id' => $wooOrderItemId,
                'latepoint_booking_id' => $row['latepoint_booking_id'] ?? null,
            ])),
        ];
    }

    private function normalizeMinutesToTime(mixed $value): string
    {
        if (blank($value)) {
            return '09:00:00';
        }

        if (is_numeric($value)) {
            $minutes = (int) $value;
            $minutes = max(0, min($minutes, 24 * 60 - 1));

            return Carbon::createFromTime(0, 0)->addMinutes($minutes)->format('H:i:s');
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
            'cancelled' => 'cancelled',
            'incident' => 'incident',
            default => 'pending',
        };
    }

    private function mapPaymentStatus(?string $status): string
    {
        return match ($status) {
            'fully_paid' => 'paid',
            'paid', 'completed' => 'paid',
            'partial' => 'partial',
            'partially_paid' => 'partial',
            'refunded' => 'refunded',
            'mismatch', 'failed' => 'mismatch',
            default => 'unpaid',
        };
    }

    private function mapWooPaymentStatus(?string $wooStatus): string
    {
        return match ($wooStatus) {
            'wc-completed', 'wc-processing' => 'paid',
            'wc-refunded' => 'refunded',
            'wc-failed' => 'mismatch',
            default => 'unpaid',
        };
    }

    private function resolveWooAdminUrl(IntegrationSetting $settings, mixed $orderId): ?string
    {
        if (blank($settings->wordpress_base_url) || blank($settings->woocommerce_admin_path) || blank($orderId)) {
            return null;
        }

        $path = str_replace('{id}', (string) $orderId, $settings->woocommerce_admin_path);

        return rtrim($settings->wordpress_base_url, '/').'/'.ltrim($path, '/');
    }

    private function resolveLatePointAdminUrl(IntegrationSetting $settings, mixed $bookingId): ?string
    {
        if (blank($settings->wordpress_base_url) || blank($settings->latepoint_admin_booking_path) || blank($bookingId)) {
            return null;
        }

        $path = str_replace('{id}', (string) $bookingId, $settings->latepoint_admin_booking_path);

        return rtrim($settings->wordpress_base_url, '/').'/'.ltrim($path, '/');
    }

    private function syncWooTransaction(Reservation $reservation, array $payload, array $rawRow): void
    {
        if (($payload['payment_status'] ?? null) !== 'paid') {
            return;
        }

        if (blank($payload['woo_order_id'] ?? null)) {
            return;
        }

        $amount = $payload['total'] ?? null;

        if (! $this->isPresent($amount)) {
            return;
        }

        Transaction::query()->updateOrCreate(
            [
                'reservation_id' => $reservation->id,
                'gateway' => 'woo',
                'gateway_ref' => (string) $payload['woo_order_id'],
            ],
            [
                'amount' => $amount,
                'currency' => $payload['currency'] ?? 'EUR',
                'status' => 'paid',
                'method' => $rawRow['payment_method'] ?? null,
                'paid_at' => $this->normalizeWooPaidAt($rawRow['paid_at_raw'] ?? null),
                'meta' => [
                    'transaction_ref' => $rawRow['transaction_ref'] ?? null,
                ],
            ],
        );
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

    private function markSyncStarted(IntegrationSetting $settings): void
    {
        $settings->forceFill([
            'last_sync_started_at' => now(),
            'last_sync_error' => null,
        ])->save();
    }

    private function markSyncFinished(IntegrationSetting $settings): void
    {
        $settings->forceFill([
            'last_sync_finished_at' => now(),
            'last_sync_failed_at' => null,
            'last_sync_error' => null,
        ])->save();
    }

    private function markSyncFailed(IntegrationSetting $settings, \Throwable $exception): void
    {
        $settings->forceFill([
            'last_sync_failed_at' => now(),
            'last_sync_error' => Str::limit($exception->getMessage(), 5000),
        ])->save();
    }
}
