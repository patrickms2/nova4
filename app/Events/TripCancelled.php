<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Trip $trip,
        public readonly float $cancellationFee
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->trip->user_id),
            new PrivateChannel('driver.'.$this->trip->driver_id),
            new PrivateChannel('trips.'.$this->trip->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'trip.cancelled';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'trip' => $this->tripData(),
            'fee' => $this->cancellationFee,
            'timestamp' => now()->toISOString(),
        ];
    }

    protected function tripData(): array
    {
        return [
            'id' => $this->trip->id,
            'status' => $this->trip->status,
            'pickup_location' => $this->trip->pickup_location,
            'dropoff_location' => $this->trip->dropoff_location,
            'scheduled_time' => $this->trip->scheduled_at?->toISOString(),
            'driver_id' => $this->trip->driver_id,
            'user_id' => $this->trip->user_id,
        ];
    }
}
