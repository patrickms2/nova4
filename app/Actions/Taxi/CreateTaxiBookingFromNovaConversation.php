<?php

namespace App\Actions\Taxi;

use App\Models\Booking;
use App\Models\Driver;
use App\Models\Location;
use App\Models\TaxiBooking;
use App\Models\TaxiService;
use App\Models\User;
use App\Models\VehicleType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateTaxiBookingFromNovaConversation
{
    /**
     * @param  array<string, mixed>  $conversation
     */
    public function handle(array $conversation): TaxiBooking
    {
        $pickupDateTime = $this->pickupDateTime($conversation);
        $reference = $this->reference($conversation, $pickupDateTime);

        $taxiBooking = DB::transaction(function () use ($conversation, $pickupDateTime, $reference): TaxiBooking {
            $existingBooking = Booking::query()
                ->where('booking_reference', $reference)
                ->first();

            if ($existingBooking?->taxiBooking) {
                return $existingBooking->taxiBooking()->with(['booking', 'pickupLocation', 'dropoffLocation'])->firstOrFail();
            }

            $taxiService = $this->taxiService();
            $vehicleType = $this->vehicleType($taxiService);
            $driver = $this->driver($taxiService);
            $pickupLocation = $this->location((string) ($conversation['origin'] ?? 'Origen indicado'));
            $dropoffLocation = $this->location((string) ($conversation['destination'] ?? 'Destino indicado'));

            $booking = $existingBooking ?? Booking::query()->create([
                'booking_reference' => $reference,
                'user_id' => $this->user()->id,
                'booking_type' => 'Taxi',
                'booking_date' => now(),
                'status' => 'Confirmed',
                'total_price' => 0,
                'discount_amount' => 0,
                'payment_status' => 'Pending',
                'special_requests' => $this->specialRequests($conversation),
                'last_updated' => now(),
            ]);

            $taxiBooking = TaxiBooking::query()->create([
                'booking_id' => $booking->id,
                'taxi_service_id' => $taxiService->id,
                'vehicle_type_id' => $vehicleType->id,
                'driver_id' => $driver->id,
                'pickup_location_id' => $pickupLocation->id,
                'dropoff_location_id' => $dropoffLocation->id,
                'pickup_date_time' => $pickupDateTime,
                'type_of_booking' => 'one_way',
                'status' => 'confirmed',
                'is_scheduled' => true,
                'is_shared' => false,
                'passenger_count' => (int) ($conversation['party_size'] ?? 1),
            ])->load(['booking', 'pickupLocation', 'dropoffLocation']);

            return $taxiBooking;
        });

        $this->createWooOrder($taxiBooking);

        return $taxiBooking;
    }

    /**
     * @param  array<string, mixed>  $conversation
     */
    private function pickupDateTime(array $conversation): CarbonImmutable
    {
        $date = (string) data_get($conversation, 'date.value', now('Europe/Madrid')->toDateString());
        $time = (string) data_get($conversation, 'time.value', '09:00');

        return CarbonImmutable::parse($date.' '.$time, 'Europe/Madrid')->utc();
    }

    /**
     * @param  array<string, mixed>  $conversation
     */
    private function reference(array $conversation, CarbonImmutable $pickupDateTime): string
    {
        return 'NOVA-TAXI-'.sha1(implode('|', [
            (string) ($conversation['tourist_phone'] ?? ''),
            Str::lower((string) ($conversation['origin'] ?? '')),
            Str::lower((string) ($conversation['destination'] ?? '')),
            $pickupDateTime->toIso8601String(),
            (string) ($conversation['party_size'] ?? ''),
        ]));
    }

    private function taxiService(): TaxiService
    {
        return TaxiService::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->firstOrFail();
    }

    private function vehicleType(TaxiService $taxiService): VehicleType
    {
        return VehicleType::query()
            ->where('taxi_service_id', $taxiService->id)
            ->where('is_active', true)
            ->orderBy('max_passengers')
            ->first()
            ?? VehicleType::query()->where('taxi_service_id', $taxiService->id)->orderBy('id')->firstOrFail();
    }

    private function driver(TaxiService $taxiService): Driver
    {
        return Driver::query()
            ->where('taxi_service_id', $taxiService->id)
            ->where('is_active', true)
            ->orderByRaw("availability_status = 'available' desc")
            ->orderBy('id')
            ->first()
            ?? Driver::query()->where('taxi_service_id', $taxiService->id)->orderBy('id')->firstOrFail();
    }

    private function location(string $name): Location
    {
        $normalizedName = trim($name) !== '' ? trim($name) : 'Ubicación indicada';
        $searchTerm = Str::of($normalizedName)
            ->lower()
            ->replace(['bodega ', 'hotel ', 'restaurante '], '')
            ->trim()
            ->toString();

        return Location::query()
            ->whereRaw('lower(name) = ?', [Str::lower($normalizedName)])
            ->first()
            ?? Location::query()
                ->whereRaw('lower(name) like ?', ['%'.$searchTerm.'%'])
                ->orderBy('id')
                ->first()
            ?? Location::query()->orderBy('id')->firstOrFail();
    }

    private function user(): User
    {
        return User::query()->orderBy('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $conversation
     */
    private function specialRequests(array $conversation): string
    {
        return trim(sprintf(
            'Nova chat taxi booking. Customer: %s. Phone: %s. Origin: %s. Destination: %s. Passengers: %s.',
            (string) ($conversation['customer_name'] ?? 'No indicado'),
            (string) ($conversation['tourist_phone'] ?? 'No indicado'),
            (string) ($conversation['origin'] ?? 'No indicado'),
            (string) ($conversation['destination'] ?? 'No indicado'),
            (string) ($conversation['party_size'] ?? 'No indicado'),
        ));
    }

    private function createWooOrder(TaxiBooking $taxiBooking): void
    {
        try {
            app(CreateTaxilanzWooOrderForTaxiBooking::class)->handle($taxiBooking);
        } catch (\Throwable $exception) {
            Log::warning('Taxilanz Woo order creation skipped after taxi booking confirmation', [
                'taxi_booking_id' => $taxiBooking->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
