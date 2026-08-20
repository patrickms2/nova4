<?php

namespace App\Livewire\Tourist;

use App\Models\Offer;
use App\Models\Ride;
use App\Models\RideRecommendation;
use App\Services\EventLogger;
use Livewire\Component;

class OfferDetailPage extends Component
{
    public Offer $offer;

    public ?int $rideId = null;

    public ?int $recommendationId = null;

    public ?Ride $ride = null;

    public ?float $distanceKm = null;

    public ?int $distanceMinutes = null;

    public bool $showMore = false;

    public bool $showWhy = false;

    public function mount(Offer $offer): void
    {
        $this->offer = $offer;
        $this->rideId = request()->integer('ride_id') ?: null;
        $this->recommendationId = request()->integer('recommendation_id') ?: null;
        $this->ride = $this->rideId ? Ride::find($this->rideId) : null;

        if ($this->ride) {
            $this->distanceKm = $this->calculateDistanceToDestination();
            $this->distanceMinutes = $this->distanceKm !== null
                ? max(3, (int) round(($this->distanceKm / 28) * 60))
                : null;
        }

        if ($this->recommendationId) {
            RideRecommendation::whereKey($this->recommendationId)->update([
                'was_clicked' => true,
                'clicked_at' => now(),
            ]);

            app(EventLogger::class)->log('recommendation_clicked', [
                'ride_id' => $this->rideId,
                'offer_id' => $this->offer->id,
                'ride_recommendation_id' => $this->recommendationId,
            ]);
        }
    }

    public function toggleMore(): void
    {
        $this->showMore = ! $this->showMore;
    }

    public function toggleWhy(): void
    {
        $this->showWhy = ! $this->showWhy;
    }

    public function goToBooking()
    {
        return $this->redirectRoute('bookings.create', [
            'offer' => $this->offer->slug,
            'ride_id' => $this->rideId,
            'recommendation_id' => $this->recommendationId,
        ], navigate: true);
    }

    public function render()
    {
        return view('livewire.tourist.offer-detail-page');
    }

    private function calculateDistanceToDestination(): ?float
    {
        if (
            ! $this->ride
            || blank($this->ride->destination_lat)
            || blank($this->ride->destination_lng)
            || blank($this->offer->lat)
            || blank($this->offer->lng)
        ) {
            return null;
        }

        $originLatitude = (float) $this->ride->destination_lat;
        $originLongitude = (float) $this->ride->destination_lng;
        $destinationLatitude = (float) $this->offer->lat;
        $destinationLongitude = (float) $this->offer->lng;
        $earthRadiusInKilometers = 6371.0;

        $latitudeDelta = deg2rad($destinationLatitude - $originLatitude);
        $longitudeDelta = deg2rad($destinationLongitude - $originLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($originLatitude))
            * cos(deg2rad($destinationLatitude))
            * sin($longitudeDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusInKilometers * $c, 1);
    }
}
