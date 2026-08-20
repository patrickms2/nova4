<?php

namespace App\Services\interfaces;

use App\Models\Rating;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

interface TripServiceInterface
{
    /**
     * Get all trips
     */
    public function getAllTrips(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a trip by ID
     *
     * @throws ModelNotFoundException
     */
    public function getTripById(int $id): Trip;

    /**
     * Get trips by user
     */
    public function getTripsByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get trips by driver
     */
    public function getTripsByDriver(int $driverId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a trip request
     *
     * @throws \Exception
     */
    public function createTripRequest(array $data): Trip;

    /**
     * Get nearby trips
     */
    public function getNearbyTrips(float $lat, float $lng, float $radius = 5.0): Collection;

    /**
     * Accept a trip
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function acceptTrip(int $tripId, int $driverId): Trip;

    /**
     * Start a trip
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function startTrip(int $tripId): Trip;

    /**
     * Calculate fare based on vehicle type and distance
     *
     * @throws ModelNotFoundException
     */
    public function calculateFare(int $vehicleTypeId, float $distance): float;

    /**
     * Complete a trip
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function completeTrip(int $tripId, float $distance, array $additionalData = []): Trip;

    /**
     * Cancel a trip
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function cancelTrip(int $tripId): Trip;

    /**
     * Create rating for a completed trip
     */
    public function createTripRating(int $userId, int $driverId, int $bookingId, float $value, ?string $comment = null): Rating;

    /**
     * Get trips by status
     */
    public function getTripsByStatus(string $status): Collection;

    /**
     * Delete a trip permanently
     */
    public function deleteTripPermanently(int $id): bool;
}
