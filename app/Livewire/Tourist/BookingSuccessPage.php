<?php

namespace App\Livewire\Tourist;

use Livewire\Component;

class BookingSuccessPage extends Component
{
    public Booking $booking;

    public bool $showImpact = false;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking->load('offer', 'ride', 'recommendation');
    }

    public function toggleImpact(): void
    {
        $this->showImpact = ! $this->showImpact;
    }

    public function render()
    {
        return view('livewire.tourist.booking-success-page');
    }
}
