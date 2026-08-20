<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Tour;
use App\Models\Villa;
use App\Models\Hotel;
use App\Models\Taxi;
use App\Models\TaxiService;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    /**
     * @var array<string, mixed>
     */
    private array $tourBookingData = [];
    private array $villaBookingData = [];
    private array $hotelBookingData = [];
    private array $packageBookingData = [];
    private array $taxiBookingData = [];


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->tourBookingData = $data['tour_booking'] ?? [];
        unset($data['tour_booking']);

        $this->villaBookingData = $data['villa_booking'] ?? [];
        unset($data['villa_booking']);

        $this->hotelBookingData = $data['hotel_booking'] ?? [];
        unset($data['hotel_booking']);

        $this->packageBookingData = $data['package_booking'] ?? [];
        unset($data['package_booking']);

        $this->taxiBookingData = $data['taxi_booking'] ?? [];
        unset($data['taxi_booking']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveTourBookingDetails($this->record, $this->tourBookingData);
        $this->saveVillaBookingDetails($this->record, $this->villaBookingData);
        $this->saveHotelBookingDetails($this->record, $this->hotelBookingData);
        $this->saveTaxiBookingDetails($this->record, $this->taxiBookingData);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveTourBookingDetails(Booking $booking, array $data): void
    {
        if ($booking->booking_type !== 'Tour' || blank($data['tour_id'] ?? null) || blank($data['schedule_id'] ?? null)) {
            return;
        }

        $tour = Tour::query()->find($data['tour_id']);

        $booking->tourBooking()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'tour_id' => $data['tour_id'],
                'schedule_id' => $data['schedule_id'],
                'number_of_adults' => max(1, (int) ($data['number_of_adults'] ?? 1)),
                'number_of_children' => max(0, (int) ($data['number_of_children'] ?? 0)),
                'base_price' => $tour?->base_price ?? 0,
                'guide_id' => $data['guide_id'] ?? null,
            ],
        );
    }
    private function saveVillaBookingDetails(Booking $booking, array $data): void
    {
        if ($booking->booking_type !== 'Villa' || blank($data['villa_id'] ?? null) || blank($data['check_in_date'] ?? null)) {
            return;
        }

        $villa = Villa::query()->find($data['villa_id']);

        $booking->villaBooking()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'villa_id' => $data['villa_id'],
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'number_of_adults' => max(1, (int) ($data['number_of_adults'] ?? 1)),
                'number_of_children' => max(0, (int) ($data['number_of_children'] ?? 0)),
                'base_price' => $villa?->base_price ?? 0,
            ],
        );
    }
    private function saveHotelBookingDetails(Booking $booking, array $data): void
    {
        if ($booking->booking_type !== 'Hotel' || blank($data['hotel_id'] ?? null) || blank($data['check_in_date'] ?? null)) {
            return;
        }

        $hotel = Hotel::query()->find($data['hotel_id']);

        $booking->hotelBooking()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'hotel_id' => $data['hotel_id'],
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'number_of_rooms' => max(1, (int) ($data['number_of_rooms'] ?? 1)),
                'number_of_guests' => max(1, (int) ($data['number_of_guests'] ?? 1)),
                'base_price' => $hotel?->base_price ?? 0,
            ],
        );
    }
    private function saveTaxiBookingDetails(Booking $booking, array $data): void
    {
        if ($booking->booking_type !== 'taxi' || blank($data['taxi_id'] ?? null) || blank($data['departure_date'] ?? null)) {
            return;
        }

        $taxi = TaxiService::query()->find($data['taxi_id']);

        $booking->taxiBooking()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'taxi_id' => $data['taxi_id'],
                'departure_date' => $data['departure_date'],
                'return_date' => $data['return_date'],
                'number_of_passengers' => max(1, (int) ($data['number_of_passengers'] ?? 1)),
                'base_price' => $taxi?->base_price ?? 0,
            ],
        );
    }
}
