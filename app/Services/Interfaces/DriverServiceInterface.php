<?php

namespace App\Services\interfaces;

use App\Models\Driver;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface DriverServiceInterface
{
    /**
     * Get all drivers
     */
    public function getAllDrivers(): Collection;

    /**
     * Get a driver by ID
     *
     * @throws ModelNotFoundException
     */
    public function getDriverById(int $id): Driver;

    /**
     * Get drivers by taxi service
     */
    public function getDriversByTaxiService(int $taxiServiceId): array;

    /**
     * Get available drivers
     */
    public function getAvailableDrivers(): Collection;

    /**
     * Create a new driver
     *
     * @throws \Exception
     */
    public function createDriver(array $data): Driver;

    /**
     * Update a driver
     *
     * @throws \Exception
     */
    public function updateDriver(int $id, array $data): Driver;

    /**
     * Create driver rating
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function createDriverRating(
        int $userId,
        int $driverId,
        int $bookingId,
        float $ratingValue,
        ?string $comment = null
    ): Rating;

    /**
     * Delete a driver
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function deleteDriver(int $id): bool;

    /**
     * Update driver location
     *
     * @throws \Exception
     */
    public function updateDriverLocation(int $driverId, float $lat, float $lng): bool;

    /**
     * Mark driver as available
     *
     * @throws \Exception
     */
    public function markAvailable(int $driverId): bool;

    /**
     * Mark driver as busy
     *
     * @throws \Exception
     */
    public function markBusy(int $driverId): bool;

    /**
     * Mark driver as offline
     *
     * @throws \Exception
     */
    public function markOffline(int $driverId): bool;

    /**
     * Get available drivers for booking
     */
    public function getAvailableDriversForBooking(
        int $taxiServiceId,
        string $bookingDateTime,
        float $pickupLat,
        float $pickupLng,
        int $radius,
        ?int $vehicleTypeId = null
    ): Collection;

    /**
     * Get bookable nearby drivers
     */
    public function getBookableNearbyDrivers(
        string $bookingDateTime,
        float $pickupLat,
        float $pickupLng,
        float $radius
    ): Collection;

    /**
     * Get driver statistics
     */
    public function getDriverStats(int $driverId): array;
}
