<?php

namespace App\Services\ExternalSync;

use App\Models\ExternalBooking;
use App\Models\ExternalCatalogItem;
use App\Models\ExternalOrder;
use App\Models\ExternalPayment;
use App\Models\ExternalSource;
use App\Models\ExternalSyncLog;
use App\Models\NovaBusiness;
use App\Models\NovaExternalBooking;
use App\Models\NovaExternalCatalogItem;
use App\Models\NovaExternalTransaction;
use App\Services\ExternalSync\Projection\ExternalProjectionManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

class ExternalSyncManager
{
    public function __construct(
        private readonly ExternalProjectionManager $projectionManager,
    ) {}

    public function run(ExternalSource $source, string $command, string $syncType, callable $callback): array
    {
        $this->markStarted($source);

        try {
            $summary = $callback($this) ?? [];
            $summary = $this->normalizeSummary($summary);

            $source->forceFill([
                'last_sync_finished_at' => now(),
                'last_sync_failed_at' => null,
                'last_sync_error' => null,
            ])->save();

            $this->log($source, $command, $syncType, 'completed', $summary);

            return $summary;
        } catch (Throwable $exception) {
            $source->forceFill([
                'last_sync_failed_at' => now(),
                'last_sync_error' => Str::limit($exception->getMessage(), 5000, ''),
            ])->save();

            $this->log($source, $command, $syncType, 'failed', [], $exception->getMessage());

            throw $exception;
        }
    }

    public function upsertCatalogItem(ExternalSource $source, array $payload): ExternalCatalogItem
    {
        $payload = $this->withSourceIdentity($source, $payload);

        $item = ExternalCatalogItem::query()->updateOrCreate(
            [
                'source_platform' => $payload['source_platform'],
                'external_id' => (string) $payload['external_id'],
                'external_item_id' => $payload['external_item_id'] ?? null,
            ],
            $payload + [
                'type' => 'product',
                'last_synced_at' => now(),
            ],
        );

        $this->syncNovaExternalCatalogItem($item);

        $this->projectionManager->project($source, $item, $payload);

        return $item;
    }

    public function upsertBooking(ExternalSource $source, array $payload): ExternalBooking
    {
        $payload = $this->withSourceIdentity($source, $payload);

        $booking = ExternalBooking::query()->updateOrCreate(
            [
                'source_platform' => $payload['source_platform'],
                'external_id' => (string) $payload['external_id'],
                'external_item_id' => $payload['external_item_id'] ?? null,
            ],
            $payload + [
                'booking_type' => 'order',
                'last_synced_at' => now(),
            ],
        );

        $this->syncNovaExternalBooking($booking);

        $this->projectionManager->project($source, $booking, $payload);

        return $booking;
    }

    public function upsertOrder(ExternalSource $source, array $payload): ExternalOrder
    {
        $payload = $this->withSourceIdentity($source, $payload);

        $order = ExternalOrder::query()->updateOrCreate(
            [
                'source_platform' => $payload['source_platform'],
                'external_id' => (string) $payload['external_id'],
            ],
            $payload + [
                'last_synced_at' => now(),
            ],
        );

        $this->projectionManager->project($source, $order, $payload);

        return $order;
    }

    public function upsertPayment(ExternalSource $source, array $payload): ExternalPayment
    {
        $payload = $this->withSourceIdentity($source, $payload);

        $payment = ExternalPayment::query()->updateOrCreate(
            [
                'source_platform' => $payload['source_platform'],
                'external_id' => (string) $payload['external_id'],
            ],
            $payload + [
                'last_synced_at' => now(),
            ],
        );

        $this->syncNovaExternalTransaction($payment);

        return $payment;
    }

    private function markStarted(ExternalSource $source): void
    {
        $source->forceFill([
            'last_sync_started_at' => now(),
            'last_sync_error' => null,
        ])->save();
    }

    private function withSourceIdentity(ExternalSource $source, array $payload): array
    {
        return array_merge($payload, [
            'server_id' => $source->server_id,
            'external_source_id' => $source->id,
            'business_name' => $source->business_name,
            'source_platform' => $source->source_platform,
            'source_label' => $source->source_label,
            'resource_type' => $payload['resource_type'] ?? $source->resource_type,
            'target_model' => $payload['target_model'] ?? $source->target_model,
        ]);
    }

    private function normalizeSummary(array $summary): array
    {
        return [
            'processed' => (int) ($summary['processed'] ?? 0),
            'created' => (int) ($summary['created'] ?? 0),
            'updated' => (int) ($summary['updated'] ?? 0),
            'skipped' => (int) ($summary['skipped'] ?? 0),
            'summary' => $summary,
        ];
    }

    public function syncNovaExternalBooking(ExternalBooking $booking): ?NovaExternalBooking
    {
        $business = NovaBusiness::query()
            ->where('name', $booking->business_name)
            ->first();

        if (! $business) {
            return null;
        }

        $startsAt = $booking->starts_at ? CarbonImmutable::parse($booking->starts_at) : null;

        $novaBooking = NovaExternalBooking::query()->firstOrNew([
            'source' => $booking->source_platform,
            'external_id' => $booking->external_id,
            'external_item_id' => $booking->external_item_id,
        ]);

        if (! $novaBooking->exists) {
            $novaBooking->id = NovaExternalBooking::query()->whereKey($booking->id)->doesntExist()
                ? $booking->id
                : ((int) NovaExternalBooking::query()->max('id')) + 1;
        }

        $novaBooking->fill([
            'nova_business_id' => $business->id,
            'source' => $booking->source_platform,
            'external_id' => $booking->external_id,
            'external_item_id' => $booking->external_item_id,
            'intent_key' => $booking->intent_key,
            'service_name' => $booking->service_name,
            'booking_date' => $startsAt?->toDateString(),
            'booking_time' => $startsAt?->toTimeString(),
            'booking_starts_at' => $booking->starts_at,
            'booking_ends_at' => $booking->ends_at,
            'attendees' => max(1, (int) ($booking->party_size ?? $booking->quantity ?? 1)),
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'customer_phone' => $booking->customer_phone,
            'total' => $booking->total,
            'currency' => $booking->currency ?: 'EUR',
            'booking_status' => $booking->status ?: 'pending',
            'payment_status' => $booking->payment_status ?: 'unpaid',
            'admin_url' => $booking->admin_url,
            'metadata' => array_merge($booking->metadata ?? [], [
                'external_booking_id' => $booking->id,
                'booking_type' => $booking->booking_type,
                'resource_type' => $booking->resource_type,
                'target_model' => $booking->target_model,
                'source_label' => $booking->source_label,
            ]),
            'source_updated_at' => $booking->source_updated_at,
            'source_fingerprint' => $booking->source_fingerprint,
            'last_synced_at' => $booking->last_synced_at ?: now(),
        ]);
        $novaBooking->save();

        return $novaBooking;
    }

    public function syncNovaExternalCatalogItem(ExternalCatalogItem $item): ?NovaExternalCatalogItem
    {
        $business = $this->resolveNovaBusiness($item->business_name);

        if (! $business) {
            return null;
        }

        return NovaExternalCatalogItem::query()->updateOrCreate(
            [
                'source' => $item->source_platform,
                'external_id' => $item->external_id,
                'external_item_id' => $item->external_item_id,
            ],
            [
                'nova_business_id' => $business->id,
                'source' => $item->source_platform,
                'external_id' => $item->external_id,
                'external_item_id' => $item->external_item_id,
                'type' => $item->type ?: 'product',
                'status' => $item->status ?: 'active',
                'name' => $item->name,
                'description' => $item->description,
                'short_description' => $item->short_description,
                'sku' => $item->sku,
                'price' => $item->price,
                'regular_price' => $item->regular_price,
                'currency' => $item->currency ?: 'EUR',
                'booking_url' => $item->booking_url,
                'purchase_url' => $item->purchase_url,
                'metadata' => array_merge($item->metadata ?? [], [
                    'external_catalog_item_id' => $item->id,
                    'business_name' => $item->business_name,
                    'source_label' => $item->source_label,
                    'admin_url' => $item->admin_url,
                ]),
                'source_updated_at' => $item->source_updated_at,
                'source_fingerprint' => $item->source_fingerprint,
                'last_synced_at' => $item->last_synced_at ?: now(),
            ],
        );
    }

    public function syncNovaExternalTransaction(ExternalPayment $payment): ?NovaExternalTransaction
    {
        $business = $this->resolveNovaBusiness($payment->business_name);

        if (! $business) {
            return null;
        }

        $booking = null;

        if (filled($payment->external_booking_id)) {
            $booking = NovaExternalBooking::query()
                ->where('source', $payment->source_platform)
                ->where('external_id', $payment->external_booking_id)
                ->first();
        }

        return NovaExternalTransaction::query()->updateOrCreate(
            [
                'source' => $payment->source_platform,
                'gateway' => $payment->processor ?: $payment->payment_method,
                'gateway_ref' => $payment->external_id,
            ],
            [
                'nova_business_id' => $business->id,
                'nova_service_id' => $booking?->nova_service_id,
                'nova_external_booking_id' => $booking?->id,
                'source' => $payment->source_platform,
                'gateway' => $payment->processor ?: $payment->payment_method,
                'gateway_ref' => $payment->external_id,
                'amount' => $payment->amount ?? 0,
                'currency' => $payment->currency ?: 'EUR',
                'status' => in_array($payment->status, ['succeeded', 'paid'], true) ? 'paid' : ($payment->status ?: 'pending'),
                'method' => $payment->payment_method,
                'paid_at' => $payment->paid_at,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'external_payment_id' => $payment->id,
                    'external_booking_id' => $payment->external_booking_id,
                    'external_order_id' => $payment->external_order_id,
                    'external_service_id' => $payment->external_service_id,
                    'source_label' => $payment->source_label,
                    'customer_name' => $payment->customer_name,
                    'customer_email' => $payment->customer_email,
                    'resource_type' => $payment->resource_type,
                    'target_model' => $payment->target_model,
                ]),
            ],
        );
    }

    private function resolveNovaBusiness(?string $businessName): ?NovaBusiness
    {
        if (blank($businessName)) {
            return null;
        }

        return NovaBusiness::query()
            ->get()
            ->first(function (NovaBusiness $business) use ($businessName): bool {
                $terms = collect($business->settings['recognition_terms'] ?? [])
                    ->push($business->name)
                    ->filter();

                return $terms->contains(fn (mixed $term): bool => str_contains(
                    mb_strtolower($businessName),
                    mb_strtolower((string) $term),
                ));
            });
    }

    private function log(ExternalSource $source, string $command, string $syncType, string $status, array $summary = [], ?string $error = null): ExternalSyncLog
    {
        $summary = $this->normalizeSummary($summary);

        return ExternalSyncLog::query()->create([
            'external_source_id' => $source->id,
            'server_id' => $source->server_id,
            'command' => $command,
            'sync_type' => $syncType,
            'status' => $status,
            'processed' => $summary['processed'],
            'created' => $summary['created'],
            'updated' => $summary['updated'],
            'skipped' => $summary['skipped'],
            'summary' => $summary['summary'],
            'error' => $error ? Str::limit($error, 5000, '') : null,
        ]);
    }
}
