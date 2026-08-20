<?php

namespace App\Events;

use App\Models\TaxiBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaxiBookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TaxiBooking $taxiBooking
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('taxi-bookings.'.$this->taxiBooking->id),
            new PrivateChannel('user.'.$this->taxiBooking->booking->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'taxi-booking.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->taxiBooking->id,
            'status' => $this->taxiBooking->status,
            'pickup_time' => $this->taxiBooking->pickup_date_time->toISOString(),
            'vehicle_type' => $this->taxiBooking->vehicleType->name,
            'estimated_distance' => $this->taxiBooking->estimated_distance,
            'passenger_count' => $this->taxiBooking->passenger_count,
            'user_id' => $this->taxiBooking->booking->user_id,
        ];
    }
}
