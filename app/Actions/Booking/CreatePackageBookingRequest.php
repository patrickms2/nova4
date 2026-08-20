<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Models\PublicBookingRequest;
use App\Models\Tour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreatePackageBookingRequest
{
    /**
     * @param  array{customer_name:string,customer_phone?:string|null,customer_email?:string|null,items:array<int,array<string,mixed>>,discount_percent?:int|float|null}  $payload
     */
    public function handle(array $payload): PublicBookingRequest
    {
        return DB::transaction(function () use ($payload): PublicBookingRequest {
            $items = collect($payload['items'])
                ->map(fn (array $item): array => $this->normalizeItem($item))
                ->values();

            $subtotal = (float) $items->sum('subtotal');
            $discountPercent = (float) ($payload['discount_percent'] ?? ($items->count() > 1 ? 10 : 0));
            $discountAmount = round($subtotal * ($discountPercent / 100), 2);
            $total = max(0, round($subtotal - $discountAmount, 2));

            $bookingRequest = PublicBookingRequest::query()->create([
                'request_reference' => $this->requestReference(),
                'type' => 'package',
                'booking_kind' => 'package',
                'service_id' => $this->primaryServiceId($items->all()),
                'service_name' => 'Nova package',
                'assignment_source' => 'ai_bot_package',
                'customer_name' => $payload['customer_name'],
                'customer_email' => $payload['customer_email'] ?? null,
                'customer_phone' => $payload['customer_phone'] ?? null,
                'status' => 'pending',
                'participants' => $this->participants($items->all()),
                'base_price' => $subtotal,
                'notes' => sprintf('Package subtotal %.2f€, discount %.2f%% (%.2f€), total %.2f€', $subtotal, $discountPercent, $discountAmount, $total),
                'remote_booking_status' => 'pending_payment',
                'payment_provider' => 'redsys',
                'payment_status' => 'pending',
                'payment_amount_cents' => (int) round($total * 100),
            ]);

            $distributedDiscount = 0.0;
            $lastIndex = $items->count() - 1;

            $items->each(function (array $item, int $index) use ($bookingRequest, $discountAmount, $subtotal, &$distributedDiscount, $lastIndex): void {
                $itemDiscount = $subtotal > 0 ? round(((float) $item['subtotal'] / $subtotal) * $discountAmount, 2) : 0.0;

                if ($index === $lastIndex) {
                    $itemDiscount = round($discountAmount - $distributedDiscount, 2);
                }

                $distributedDiscount += $itemDiscount;

                $bookingRequest->items()->create([
                    ...$item,
                    'discount_amount' => $itemDiscount,
                    'total' => max(0, round((float) $item['subtotal'] - $itemDiscount, 2)),
                    'remote_booking_status' => 'pending_payment',
                ]);
            });

            return $bookingRequest->refresh()->load('items');
        });
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item): array
    {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $unitPrice = round((float) ($item['unit_price'] ?? 0), 2);
        $startsAt = Arr::get($item, 'starts_at');

        return [
            'item_type' => (string) $item['item_type'],
            'service_id' => $item['service_id'] ?? null,
            'service_name' => $item['service_name'] ?? null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round($quantity * $unitPrice, 2),
            'currency' => (string) ($item['currency'] ?? 'EUR'),
            'starts_at' => $startsAt ? CarbonImmutable::parse((string) $startsAt, 'Europe/Madrid') : null,
            'metadata' => $item['metadata'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function primaryServiceId(array $items): ?int
    {
        $serviceId = collect($items)->pluck('service_id')->filter()->first();

        if ($serviceId) {
            return (int) $serviceId;
        }

        return Tour::query()->where('is_active', true)->orderBy('id')->value('id');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function participants(array $items): int
    {
        return max(1, (int) collect($items)->max('quantity'));
    }

    private function requestReference(): string
    {
        return 'PKG-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
    }
}
