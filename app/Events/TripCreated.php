<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('trips');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'trip.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->trip->id,
            'pickup_location' => [
                'lat' => $this->trip->pickup_location->getLat(),
                'lng' => $this->trip->pickup_location->getLng(),
            ],
            'user_id' => $this->trip->user_id,
            'status' => $this->trip->status,
            'distance_km' => $this->trip->distance_km,
            'fare' => $this->trip->fare,
            'created_at' => $this->trip->created_at->toDateTimeString(),
        ];
    }
}
