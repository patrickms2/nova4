<?php

namespace App\Events;

use App\Models\TaxiBooking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaxiBookingStatusUpdated implements ShouldBroadcast
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
        return [
            new Channel('taxi-bookings'),
            new PrivateChannel('taxi-booking.'.$this->taxiBooking->id),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'taxi-booking.status-updated';
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
            'status' => $this->taxiBooking->status,
            'driver_id' => $this->taxiBooking->driver_id,
            'vehicle_id' => $this->taxiBooking->vehicle_id,
            'updated_at' => $this->taxiBooking->updated_at,
        ];
    }
}
