<?php

namespace App\Services;

use App\Models\Event;

class EventLogger
{
    /**
     * Registra un evento del funnel.
     */
    public function log(string $type, array $params = []): Event
    {
        return Event::create([
            'event_type' => $type,
            'ride_id' => $params['ride_id'] ?? null,
            'offer_id' => $params['offer_id'] ?? null,
            'booking_id' => $params['booking_id'] ?? null,
            'ride_recommendation_id' => $params['ride_recommendation_id'] ?? null,
            'session_id' => session()->getId(),
            'value_amount' => $params['value_amount'] ?? 0,
            'meta' => $params['meta'] ?? null,
            'created_at' => now(),
        ]);
    }
}
