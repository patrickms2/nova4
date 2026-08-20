<?php

namespace App\Events;

use App\Models\Vehicle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\LogBatch;

class VehicleUpdated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vehicle;

    public $broadcastQueue = 'high-priority';

    public $afterCommit = true;

    public function __construct(Vehicle $vehicle)
    {
        $vehicle->load(['vehicleType', 'taxiService']);
        LogBatch::startBatch();
        activity()
            ->performedOn($vehicle)
            ->withProperties($vehicle->getChanges())
            ->log('Vehicle updated');
        LogBatch::endBatch();
        $this->vehicle = $vehicle;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel("vehicle.{$this->vehicle->id}"),
            new PresenceChannel("driver-monitor.{$this->vehicle->id}"),
            new Channel("taxi-service.{$this->vehicle->taxi_service_id}.updates"),
        ];
    }

    public function broadcastWith()
    {
        return [
            'api_version' => '1.1',
            'data' => [
                'vehicle' => $this->vehicle->makeHidden(['created_at', 'updated_at']),
                'relationships' => [
                    'type' => $this->vehicle->vehicleType,
                    'service' => $this->vehicle->taxiService,
                ],
            ],
            'meta' => [
                'event_time' => now()->toISOString(),
                'initiator' => optional(Auth::user())->id,
            ],
        ];
    }

    public function broadcastAs()
    {
        return 'vehicle.updated';
    }
}
