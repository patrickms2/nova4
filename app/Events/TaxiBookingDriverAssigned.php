<?php

namespace App\Events;

use App\Models\Driver;
use App\Models\TaxiBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaxiBookingDriverAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TaxiBooking $taxiBooking,
        public readonly Driver $driver
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('taxi-bookings.'.$this->taxiBooking->id),
            new PrivateChannel('driver.'.$this->driver->id),
            new PrivateChannel('user.'.$this->taxiBooking->booking->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'taxi-booking.driver-assigned';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->taxiBooking->id,
            'driver' => [
                'id' => $this->driver->id,
                'name' => $this->driver->full_name,
                'vehicle' => $this->taxiBooking->vehicle?->make_model,
                'rating' => $this->driver->rating,
                'phone' => $this->driver->formatted_phone,
            ],
            'estimated_arrival_time' => now()->addMinutes(5)->toISOString(),
            'tracking_url' => route('bookings.tracking', $this->taxiBooking),
        ];
    }
}
