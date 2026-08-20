<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripStatusChanged implements ShouldBroadcast
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
        return [
            new PrivateChannel('trip.'.$this->trip->id),
            new PrivateChannel('user.'.$this->trip->user_id),
            new PrivateChannel('driver.'.$this->trip->driver_id),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'trip.status_changed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'trip_id' => $this->trip->id,
            'user_id' => $this->trip->user_id,
            'driver_id' => $this->trip->driver_id,
            'status' => $this->trip->status,
            'started_at' => $this->trip->started_at,
            'completed_at' => $this->trip->completed_at,
            'fare' => $this->trip->fare,
        ];
    }
}
