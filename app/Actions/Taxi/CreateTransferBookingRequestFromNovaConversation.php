<?php

declare(strict_types=1);

namespace App\Actions\Taxi;

use App\Models\PublicBookingRequest;
use App\Models\Tool;
use App\Models\Tour;
use App\Services\ExternalSync\RemoteBookingCreator;
use App\Services\ToolExecutor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class CreateTransferBookingRequestFromNovaConversation
{
    public function __construct(
        private RemoteBookingCreator $remoteBookingCreator,
        private ToolExecutor $toolExecutor,
    ) {}

    /**
     * @param  array<string, mixed>  $conversation
     */
    public function handle(array $conversation): PublicBookingRequest
    {
        $tour = $this->transferService();
        $origin = (string) ($conversation['origin'] ?? '');
        $destination = (string) ($conversation['destination'] ?? '');
        $passengers = max(1, (int) ($conversation['party_size'] ?? 1));
        $pickupDate = (string) data_get($conversation, 'date.value', now('Europe/Madrid')->toDateString());
        $pickupTime = (string) data_get($conversation, 'time.value', '09:00');
        $pickupDateTime = CarbonImmutable::parse($pickupDate.' '.$pickupTime, 'Europe/Madrid');
        $price = $this->estimatePrice($origin, $destination, $passengers);

        $bookingRequest = PublicBookingRequest::query()->create([
            'request_reference' => $this->requestReference(),
            'type' => 'transfer',
            'booking_kind' => 'transfer',
            'service_id' => $tour->getKey(),
            'service_name' => $tour->name,
            'assignment_source' => 'ai_bot',
            'customer_name' => $conversation['customer_name'] ?? 'Cliente transfer',
            'customer_email' => data_get($conversation, 'customer_email'),
            'customer_phone' => $conversation['customer_phone'] ?? $conversation['tourist_phone'] ?? null,
            'status' => 'pending',
            'passengers' => $passengers,
            'adults' => $passengers,
            'children' => 0,
            'participants' => $passengers,
            'pickup_date_time' => $pickupDateTime,
            'tour_date' => $pickupDateTime->toDateString(),
            'tour_schedule' => $pickupDateTime->format('H:i'),
            'base_price' => $price,
            'pickup_address' => $origin,
            'dropoff_address' => $destination,
            'notes' => $conversation['preferences'] ?? null,
            'payment_provider' => 'redsys',
            'payment_status' => 'pending',
            'payment_amount_cents' => (int) round($price * 100),
        ]);

        $remoteBooking = $this->remoteBookingCreator->create($bookingRequest, $tour);

        if (($remoteBooking['status'] ?? null) === 'created') {
            $bookingRequest->forceFill([
                'base_price' => $price,
            ])->save();
        }

        return $bookingRequest->refresh();
    }

    private function transferService(): Tour
    {
        $tour = Tour::query()
            ->where('is_active', true)
            ->whereHas('externalSyncMappings', fn ($query) => $query->where('source_platform', 'woo')->where('resource_type', 'tour_route'))
            ->orderBy('id')
            ->first();

        if (! $tour) {
            throw new RuntimeException('No active Woo transfer route service found.');
        }

        return $tour;
    }

    private function estimatePrice(string $origin, string $destination, int $passengers): float
    {
        $tool = Tool::query()
            ->where('name', 'transfer_price_estimate')
            ->where('is_active', true)
            ->first();

        if (! $tool) {
            throw new RuntimeException('Transfer price estimate tool not found.');
        }

        $result = $this->toolExecutor->execute($tool, [
            'pickup_location' => $origin,
            'dropoff_location' => $destination,
            'passengers' => $passengers,
        ]);

        $data = is_string($result) ? json_decode($result, true) : $result;
        $price = data_get($data, 'estimated_price');

        if (! is_numeric($price) || (float) $price <= 0) {
            throw new RuntimeException('Transfer price could not be calculated.');
        }

        return (float) $price;
    }

    private function requestReference(): string
    {
        return 'REQ-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
    }
}
