<?php

namespace App\Services\interfaces;

use App\Models\TaxiBooking;
use App\Models\Trip;

interface CancellationFeeCalculatorInterface
{
    /**
     * Calculate cancellation fee for a taxi booking
     */
    public function calculateForTaxiBooking(TaxiBooking $booking): float;

    /**
     * Calculate cancellation fee for a trip
     */
    public function calculateForTrip(Trip $trip): float;
}
