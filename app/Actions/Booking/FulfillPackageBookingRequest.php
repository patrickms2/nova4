<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Models\PublicBookingRequest;
use App\Models\PublicBookingRequestItem;
use App\Models\Tour;
use App\Services\ExternalSync\RemoteBookingCreator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class FulfillPackageBookingRequest
{
    public function __construct(
        private RemoteBookingCreator $remoteBookingCreator,
    ) {}

    public function handle(PublicBookingRequest $package): PublicBookingRequest
    {
        if ($package->type !== 'package' || $package->payment_status !== 'paid') {
            return $package;
        }

        $package->loadMissing('items');

        foreach ($package->items as $item) {
            if ($item->remote_booking_status === 'created') {
                continue;
            }

            if ($item->item_type === 'winery_visit' && blank($package->customer_email)) {
                $item->forceFill([
                    'remote_booking_status' => 'pending_manual',
                    'metadata' => array_merge($item->metadata ?? [], [
                        'manual_reason' => 'Customer email is required by LatePoint.',
                    ]),
                ])->save();

                continue;
            }

            try {
                $this->fulfillItem($package, $item);
            } catch (Throwable $exception) {
                $item->forceFill([
                    'remote_booking_status' => 'failed',
                    'metadata' => array_merge($item->metadata ?? [], [
                        'fulfillment_error' => Str::limit($exception->getMessage(), 1000, ''),
                    ]),
                ])->save();

                Log::warning('Package item fulfillment failed', [
                    'public_booking_request_id' => $package->id,
                    'public_booking_request_item_id' => $item->id,
                    'item_type' => $item->item_type,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $package->refresh()->load('items');

        $statuses = $package->items->pluck('remote_booking_status')->all();
        $package->forceFill([
            'remote_booking_status' => in_array('failed', $statuses, true)
                ? 'partially_failed'
                : ($package->items->every(fn (PublicBookingRequestItem $item): bool => $item->remote_booking_status === 'created') ? 'created' : 'partially_created'),
        ])->save();

        return $package->refresh()->load('items');
    }

    private function fulfillItem(PublicBookingRequest $package, PublicBookingRequestItem $item): void
    {
        $service = $this->serviceForItem($item);
        $startsAt = $item->starts_at
            ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $item->starts_at->format('Y-m-d H:i:s'), 'Europe/Madrid')
            : null;

        $child = PublicBookingRequest::query()->create([
            'request_reference' => $package->request_reference.'-'.$item->id,
            'type' => $this->requestTypeForItem($item),
            'booking_kind' => $item->item_type,
            'service_id' => $service?->getKey(),
            'service_name' => $item->service_name,
            'assignment_source' => 'package_fulfillment',
            'customer_name' => $package->customer_name,
            'customer_email' => $package->customer_email ?: 'reservas+'.(int) $package->id.'@novahub.test',
            'customer_phone' => $package->customer_phone,
            'status' => 'pending',
            'guests' => $item->quantity,
            'passengers' => $this->metadataInt($item, 'passengers', $item->quantity),
            'adults' => $item->quantity,
            'children' => 0,
            'participants' => $item->quantity,
            'reservation_date' => $item->item_type === 'restaurant' ? $startsAt?->toDateString() : null,
            'reservation_time' => $item->item_type === 'restaurant' ? $startsAt?->format('H:i') : null,
            'pickup_date_time' => $item->item_type === 'transfer' ? $startsAt?->timezone('UTC') : null,
            'tour_date' => in_array($item->item_type, ['transfer', 'winery_visit', 'tour'], true) ? $startsAt?->toDateString() : null,
            'tour_schedule' => in_array($item->item_type, ['transfer', 'winery_visit', 'tour'], true) ? $startsAt?->format('H:i') : null,
            'base_price' => $item->total,
            'pickup_address' => $this->metadataString($item, 'origin'),
            'dropoff_address' => $this->metadataString($item, 'destination'),
            'notes' => 'Fulfilled from package '.$package->request_reference,
            'payment_provider' => 'redsys',
            'payment_status' => 'paid',
            'payment_amount_cents' => (int) round(((float) $item->total) * 100),
            'payment_order' => $package->payment_order,
            'payment_reference' => $package->payment_reference,
            'payment_paid_at' => $package->payment_paid_at,
            'payment_raw' => $package->payment_raw,
        ]);

        $result = $service ? $this->remoteBookingCreator->create($child, $service) : ['status' => 'skipped'];

        $item->forceFill([
            'remote_booking_status' => $result['status'] ?? $child->remote_booking_status,
            'remote_source_platform' => $result['source_platform'] ?? $child->remote_source_platform,
            'remote_external_id' => $result['external_id'] ?? $child->remote_external_id,
            'metadata' => array_merge($item->metadata ?? [], [
                'child_public_booking_request_id' => $child->id,
                'remote_result' => $result,
            ]),
        ])->save();
    }

    private function requestTypeForItem(PublicBookingRequestItem $item): string
    {
        return match ($item->item_type) {
            'transfer' => 'transfer',
            'restaurant' => 'restaurant',
            default => 'tour',
        };
    }

    private function serviceForItem(PublicBookingRequestItem $item): ?Tour
    {
        if ($item->service_id) {
            return Tour::query()->find($item->service_id);
        }

        if ($item->item_type === 'transfer') {
            return Tour::query()
                ->where('is_active', true)
                ->whereHas('externalSyncMappings', fn ($query) => $query->where('source_platform', 'woo')->where('resource_type', 'tour_route'))
                ->orderBy('id')
                ->first();
        }

        if (in_array($item->item_type, ['winery_visit', 'tour'], true)) {
            return Tour::query()
                ->where('is_active', true)
                ->whereHas('externalSyncMappings', fn ($query) => $query->whereIn('resource_type', ['tour_visit', 'tour']))
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    private function metadataString(PublicBookingRequestItem $item, string $key): ?string
    {
        $value = data_get($item->metadata, $key);

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    private function metadataInt(PublicBookingRequestItem $item, string $key, int $default): int
    {
        $value = data_get($item->metadata, $key);

        return is_numeric($value) ? (int) $value : $default;
    }
}
