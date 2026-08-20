<?php

namespace App\Events;

use App\Models\VehicleType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleTypeUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vehicleType;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(VehicleType $vehicleType)
    {
        $this->vehicleType = $vehicleType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return [
            new Channel('vehicle-types'),
            new PrivateChannel('vehicle-type.'.$this->vehicleType->id),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'vehicle-type.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->vehicleType->id,
            'name' => $this->vehicleType->name,
            'description' => $this->vehicleType->description,
            'taxi_service_id' => $this->vehicleType->taxi_service_id,
            'max_passengers' => $this->vehicleType->max_passengers,
            'price_per_km' => $this->vehicleType->price_per_km,
            'base_price' => $this->vehicleType->base_price,
            'is_active' => $this->vehicleType->is_active,
        ];
    }
}
