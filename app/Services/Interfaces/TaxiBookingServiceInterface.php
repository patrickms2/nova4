<?php

namespace App\Services\interfaces;

use App\Exceptions\NoDriversAvailableException;
use App\Models\TaxiBooking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface TaxiBookingServiceInterface
{
    /**
     * Get all taxi bookings
     */
    public function getAllTaxiBookings(): Collection;

    /**
     * Get a taxi booking by ID
     *
     * @throws ModelNotFoundException
     */
    public function getTaxiBookingById(int $id): TaxiBooking;

    /**
     * Get taxi bookings by user ID
     */
    public function getTaxiBookingsByUserId(int $userId): Collection;

    /**
     * Create a new taxi booking
     *
     * @throws \Exception
     */
    public function createTaxiBooking(array $data): TaxiBooking;

    /**
     * Update a taxi booking
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function updateTaxiBooking(int $id, array $data): TaxiBooking;

    /**
     * Cancel a taxi booking
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function cancelTaxiBooking(int $id): TaxiBooking;

    /**
     * Assign a driver to a booking
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function assignDriver(int $bookingId, int $driverId, ?int $vehicleId = null): TaxiBooking;

    /**
     * Find available shared rides
     *
     * @throws \Exception
     */
    public function findAvailableSharedRides(
        int $pickupLocationId,
        int $dropoffLocationId,
        string $pickupDateTime,
        int $passengerCount
    ): Collection;

    /**
     * Book a taxi with automatic driver assignment
     *
     * @throws NoDriversAvailableException
     * @throws \Exception
     */
    public function bookTaxi(
        int $taxiServiceId,
        string $pickupTime,
        float $pickupLat,
        float $pickupLng,
        int $radius,
        array $bookingDetails
    ): TaxiBooking;

    /**
     * Complete a booking
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function completeBooking(int $bookingId): TaxiBooking;

    /**
     * Get upcoming bookings
     */
    public function getUpcomingBookings(): Collection;

    /**
     * Get scheduled bookings
     */
    public function getScheduledBookings(): Collection;
}
