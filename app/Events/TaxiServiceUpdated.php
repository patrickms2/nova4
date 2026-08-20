<?php

namespace App\Events;

use App\Models\TaxiService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaxiServiceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $taxiService;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TaxiService $taxiService)
    {
        $this->taxiService = $taxiService;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return [
            new Channel('taxi-services'),
            new PrivateChannel('taxi-service.'.$this->taxiService->id),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'taxi-service.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->taxiService->id,
            'name' => $this->taxiService->name,
            'description' => $this->taxiService->description,
            'location_id' => $this->taxiService->location_id,
            'is_active' => $this->taxiService->is_active,
            'updated_at' => $this->taxiService->updated_at->toIso8601String(),
        ];
    }
}
