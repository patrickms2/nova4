<?php

namespace App\Livewire\Tourist;

use App\Models\Offer;
use App\Models\RideRecommendation;
use App\Services\EventLogger;
use Illuminate\Support\Str;
use Livewire\Component;

class BookingPage extends Component
{
    public Offer $offer;

    public ?int $rideId = null;

    public ?int $recommendationId = null;

    public string $customerName = '';

    public string $customerEmail = '';

    public string $customerPhone = '';

    public int $partySize = 2;

    public ?string $bookingFor = null;

    public ?string $notes = null;

    public function mount(Offer $offer): void
    {
        $this->offer = $offer;
        $this->rideId = request()->integer('ride_id') ?: null;
        $this->recommendationId = request()->integer('recommendation_id') ?: null;
    }

    public function submit()
    {
        $this->validate([
            'customerName' => ['required', 'string', 'max:255'],
            'customerEmail' => ['nullable', 'email'],
            'customerPhone' => ['nullable', 'string', 'max:50'],
            'partySize' => ['required', 'integer', 'min:1', 'max:12'],
            'bookingFor' => ['nullable', 'date'],
        ]);

        app(EventLogger::class)->log('booking_started', [
            'ride_id' => $this->rideId,
            'offer_id' => $this->offer->id,
            'ride_recommendation_id' => $this->recommendationId,
        ]);

        $booking = Booking::create([
            'uuid' => (string) Str::uuid(),
            'ride_id' => $this->rideId,
            'offer_id' => $this->offer->id,
            'ride_recommendation_id' => $this->recommendationId,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail ?: null,
            'customer_phone' => $this->customerPhone ?: null,
            'party_size' => $this->partySize,
            'booking_for' => $this->bookingFor ?: null,
            'status' => 'confirmed',
            'amount' => $this->offer->price_from,
            'commission_amount' => $this->offer->price_from ? round($this->offer->price_from * 0.10, 2) : null,
            'notes' => $this->notes ?: null,
        ]);

        if ($this->recommendationId) {
            RideRecommendation::whereKey($this->recommendationId)->update([
                'was_booked' => true,
                'booked_at' => now(),
            ]);
        }

        app(EventLogger::class)->log('booking_completed', [
            'ride_id' => $this->rideId,
            'offer_id' => $this->offer->id,
            'booking_id' => $booking->id,
            'ride_recommendation_id' => $this->recommendationId,
        ]);

        return $this->redirectRoute('bookings.success', ['booking' => $booking->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.tourist.booking-page');
    }
}
