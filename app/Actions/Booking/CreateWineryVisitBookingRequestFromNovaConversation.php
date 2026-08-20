<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Models\PublicBookingRequest;
use App\Models\Tour;
use App\Services\ExternalSync\RemoteBookingCreator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class CreateWineryVisitBookingRequestFromNovaConversation
{
    public function __construct(
        private RemoteBookingCreator $remoteBookingCreator,
    ) {}

    /**
     * @param  array<string, mixed>  $conversation
     */
    public function handle(array $conversation): PublicBookingRequest
    {
        $tour = $this->wineryService();
        $partySize = max(1, (int) ($conversation['party_size'] ?? 1));
        $visitDate = (string) data_get($conversation, 'date.value', now('Europe/Madrid')->toDateString());
        $visitTime = (string) data_get($conversation, 'time.value', '12:00');
        $visitDateTime = CarbonImmutable::parse($visitDate.' '.$visitTime, 'Europe/Madrid');
        $price = $tour->base_price ?? 15.0;

        $bookingRequest = PublicBookingRequest::query()->create([
            'request_reference' => $this->requestReference(),
            'type' => 'tour',
            'booking_kind' => 'tour',
            'service_id' => $tour->getKey(),
            'service_name' => $tour->name,
            'assignment_source' => 'ai_bot',
            'customer_name' => $conversation['customer_name'] ?? 'Cliente visita',
            'customer_email' => data_get($conversation, 'customer_email'),
            'customer_phone' => $conversation['customer_phone'] ?? $conversation['tourist_phone'] ?? null,
            'status' => 'pending',
            'passengers' => $partySize,
            'adults' => $partySize,
            'children' => 0,
            'participants' => $partySize,
            'tour_date' => $visitDateTime->toDateString(),
            'tour_schedule' => $visitDateTime->format('H:i'),
            'base_price' => $price,
            'notes' => $conversation['preferences'] ?? null,
            'payment_provider' => 'redsys',
            'payment_status' => 'pending',
            'payment_amount_cents' => (int) round($price * $partySize * 100),
        ]);

        try {
            $remoteBooking = $this->remoteBookingCreator->create($bookingRequest, $tour);

            if (($remoteBooking['status'] ?? null) === 'created') {
                $bookingRequest->forceFill([
                    'base_price' => $price,
                ])->save();
            }
        } catch (\Throwable $exception) {
            Log::warning('Nova winery visit remote booking creation skipped before payment link', [
                'booking_request_id' => $bookingRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return $bookingRequest->refresh();
    }

    private function wineryService(): Tour
    {
        $tour = Tour::query()
            ->where('is_active', true)
            ->whereHas('externalSyncMappings', fn ($query) => $query->where('source_platform', 'latepoint')->whereIn('resource_type', ['tour_visit', 'tour', 'service']))
            ->orderBy('id')
            ->first();

        if (! $tour) {
            throw new RuntimeException('No active LatePoint winery visit service found.');
        }

        return $tour;
    }

    private function requestReference(): string
    {
        return 'REQ-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
    }
}
