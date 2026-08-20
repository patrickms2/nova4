<?php

namespace App\Events;

use App\Models\TaxiBooking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaxiBookingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $taxiBooking;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TaxiBooking $taxiBooking)
    {
        $this->taxiBooking = $taxiBooking;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('taxi-bookings');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'taxi-booking.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->taxiBooking->id,
            'booking_id' => $this->taxiBooking->booking_id,
            'taxi_service_id' => $this->taxiBooking->taxi_service_id,
            'vehicle_type_id' => $this->taxiBooking->vehicle_type_id,
            'driver_id' => $this->taxiBooking->driver_id,
            'vehicle_id' => $this->taxiBooking->vehicle_id,
            'status' => $this->taxiBooking->status,
            'updated_at' => $this->taxiBooking->updated_at,
        ];
    }
}
