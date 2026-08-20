<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Offer;
use App\Models\Ride;
use App\Models\RideRecommendation;

class EventTracker
{
    public function log(string $eventType, ?Ride $ride = null, ?Offer $offer = null, ?Booking $booking = null, ?RideRecommendation $recommendation = null, array $meta = []): Event
    {
        return Event::create([
            'event_type' => $eventType,
            'ride_id' => $ride?->id,
            'offer_id' => $offer?->id,
            'booking_id' => $booking?->id,
            'ride_recommendation_id' => $recommendation?->id,
            'session_id' => session()->getId(),
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }

    public function logOncePerSession(string $eventType, string $dedupeKey, ?Ride $ride = null, ?Offer $offer = null, ?Booking $booking = null, ?RideRecommendation $recommendation = null, array $meta = []): ?Event
    {
        $logged = session()->get('event_dedupe', []);

        if (in_array($dedupeKey, $logged, true)) {
            return null;
        }

        $logged[] = $dedupeKey;
        session()->put('event_dedupe', $logged);

        return $this->log($eventType, $ride, $offer, $booking, $recommendation, $meta);
    }
}
