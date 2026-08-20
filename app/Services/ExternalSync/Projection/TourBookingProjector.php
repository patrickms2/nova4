<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\Booking;
use App\Models\ExternalSyncMapping;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\TourSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TourBookingProjector implements Projector
{
    public function project(ExternalProjectionPayload $payload): Model
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasTable('tour_bookings') || ! Schema::hasTable('tour_schedules')) {
            return $payload->stagingRecord;
        }

        $raw = $payload->raw();
        $serviceId = (string) ($raw['service_id'] ?? data_get($raw, 'service.id') ?? data_get($raw, 'service.service_id') ?? '');
        $tour = $this->resolveTour($payload, $serviceId);

        if (! $tour) {
            return $payload->stagingRecord;
        }

        $startsAt = $this->resolveStartsAt($payload);
        $schedule = $this->resolveSchedule($tour, $startsAt);
        $participants = (int) ($raw['participants'] ?? data_get($raw, 'party_size') ?? 1);
        $adults = (int) ($raw['adults'] ?? 0);
        $children = (int) ($raw['children'] ?? 0);

        $adults = $adults > 0 ? $adults : max(1, $participants);
        $children = max(0, $children);
        $unitPrice = $this->nullableDecimal($raw['unit_price'] ?? data_get($raw, 'base_price') ?? $tour->base_price);
        $totalPrice = $this->nullableDecimal($raw['total'] ?? data_get($raw, 'payment_total'));
        if ($totalPrice <= 0) {
            $totalPrice = $unitPrice * $adults;
        }

        $booking = Booking::query()->updateOrCreate(
            ['booking_reference' => $this->bookingReference($payload)],
            [
                'user_id' => $this->defaultUserId(),
                'booking_type' => 'Tour',
                'booking_date' => $startsAt?->toDateTimeString() ?? now(),
                'status' => $this->bookingStatus($raw['status'] ?? null),
                'total_price' => $totalPrice,
                'discount_amount' => 0,
                'payment_status' => $this->paymentStatus($raw['payment_status'] ?? null),
                'special_requests' => $this->notes($raw),
                'cancellation_reason' => null,
                'last_updated' => now(),
            ]
        );

        return TourBooking::query()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'tour_id' => $tour->id,
                'schedule_id' => $schedule->id,
                'number_of_adults' => $adults,
                'number_of_children' => $children,
                'base_price' => $unitPrice,
                'guide_id' => null,
            ]
        );
    }

    private function resolveTour(ExternalProjectionPayload $payload, string $serviceId): ?Tour
    {
        if ($serviceId === '') {
            return null;
        }

        $mapping = ExternalSyncMapping::query()
            ->where('source_platform', $payload->source->source_platform)
            ->whereIn('resource_type', ['tour_visit', 'tour_route'])
            ->where('target_model', 'tour')
            ->where('external_id', $serviceId)
            ->latest('last_synced_at')
            ->first();

        if (! $mapping) {
            return null;
        }

        return Tour::query()->find($mapping->target_id);
    }

    private function resolveStartsAt(ExternalProjectionPayload $payload): ?CarbonImmutable
    {
        $raw = $payload->raw();
        $value = data_get($raw, 'start_datetime')
            ?: data_get($raw, 'starts_at')
            ?: data_get($raw, 'start_date_time')
            ?: $payload->payload['starts_at']
            ?: null;

        return blank($value) ? null : CarbonImmutable::parse($value);
    }

    private function resolveSchedule(Tour $tour, ?CarbonImmutable $startsAt): TourSchedule
    {
        $startsAt = $startsAt ?: CarbonImmutable::now()->addDay();
        $date = $startsAt->toDateString();

        $existing = TourSchedule::query()
            ->where('tour_id', $tour->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return TourSchedule::query()->create([
            'tour_id' => $tour->id,
            'start_date' => $date,
            'end_date' => $date,
            'start_time' => $startsAt->toDateTimeString(),
            'available_spots' => max(1, (int) ($tour->max_capacity ?? 1)),
            'price' => $tour->base_price ?? 0,
            'is_active' => true,
        ]);
    }

    private function bookingReference(ExternalProjectionPayload $payload): string
    {
        return Str::upper(Str::substr($payload->source->source_platform, 0, 3)).'-'.$payload->externalId();
    }

    private function defaultUserId(): int
    {
        return (int) (User::query()->min('id') ?? 1);
    }

    private function bookingStatus(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'cancelled', 'canceled' => 'Cancelled',
            'completed', 'done' => 'Completed',
            'confirmed', 'approved' => 'Confirmed',
            default => 'Pending',
        };
    }

    private function paymentStatus(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'paid', 'completed' => 'Paid',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
            default => 'Pending',
        };
    }

    private function notes(array $raw): ?string
    {
        $notes = (string) ($raw['notes'] ?? $raw['comment'] ?? data_get($raw, 'customer.notes') ?? '');

        return $notes === '' ? null : Str::limit($notes, 1000, '');
    }

    private function nullableDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) $value;
    }
}
