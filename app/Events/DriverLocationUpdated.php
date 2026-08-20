<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driverId;

    public $lat;

    public $lng;

    public $lastSeenAt;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $lastSeenAt
     * @return void
     */
    public function __construct(int $driverId, float $lat, float $lng, $lastSeenAt = null)
    {
        $this->driverId = $driverId;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->lastSeenAt = $lastSeenAt ?? now();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return ['drivers-channel'];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'driver.location_updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'driver_id' => $this->driverId,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'last_seen_at' => $this->lastSeenAt,
        ];
    }
}
