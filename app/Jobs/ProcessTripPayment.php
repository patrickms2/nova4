<?php

namespace App\Jobs;

use App\Models\Trip;
use App\Repositories\TripRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTripPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $trip;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TripRepository $tripRepository)
    {
        // Load required relationships
        $this->trip->loadMissing('taxiService');

        // Calculate and update the fare
        $tripRepository->calculateFare($this->trip);

        // Update the trip status
        $this->trip->update(['status' => 'completed']);
    }
}
