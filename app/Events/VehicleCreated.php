<?php

namespace App\Events;

use App\Models\Vehicle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleCreated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vehicle;

    public $broadcastQueue = 'vehicle-events';

    public function __construct(Vehicle $vehicle)
    {
        $vehicle->loadMissing(['vehicleType', 'taxiService']);
        $this->vehicle = $vehicle;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel("taxi-service.{$this->vehicle->taxi_service_id}.vehicles"),
            new Channel('vehicle-activity'),
        ];
    }

    public function broadcastAs()
    {
        return 'vehicle.created';
    }
}

// VehicleDeleted.php
class VehicleDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vehicleId;

    public $taxiServiceId;

    public function __construct(int $vehicleId, int $taxiServiceId)
    {
        $this->vehicleId = $vehicleId;
        $this->taxiServiceId = $taxiServiceId;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel("taxi-service.{$this->taxiServiceId}.vehicles"),
            new Channel('vehicle-activity'),
        ];
    }

    public function broadcastAs()
    {
        return 'vehicle.deleted';
    }
}

// VehicleStatusChanged.php
class VehicleStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vehicle;

    public $previousStatus;

    public function __construct(Vehicle $vehicle, bool $previousStatus)
    {
        $this->vehicle = $vehicle;
        $this->previousStatus = $previousStatus;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel("vehicle.{$this->vehicle->id}"),
            new Channel("taxi-service.{$this->vehicle->taxi_service_id}.status-updates"),
        ];
    }
}
