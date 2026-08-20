<?php

namespace App\Services\interfaces;

use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface VehicleServiceInterface
{
    /**
     * Get all vehicles
     */
    public function getAllVehicles(): Collection;

    /**
     * Get a vehicle by ID
     *
     * @throws ModelNotFoundException
     */
    public function getVehicleById(int $id): Vehicle;

    /**
     * Get vehicles by taxi service
     */
    public function getVehiclesByTaxiService(int $taxiServiceId): Collection;

    /**
     * Get vehicles by vehicle type
     */
    public function getVehiclesByType(int $vehicleTypeId): Collection;

    /**
     * Get available vehicles for booking
     */
    public function getAvailableVehiclesForBooking(
        int $taxiServiceId,
        int $vehicleTypeId,
        string $bookingDateTime
    ): Collection;

    /**
     * Create a new vehicle
     *
     * @throws \Exception
     */
    public function createVehicle(array $data): Vehicle;

    /**
     * Update a vehicle
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function updateVehicle(int $id, array $data): Vehicle;

    /**
     * Delete a vehicle
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function deleteVehicle(int $id): bool;

    /**
     * Check if a vehicle is available at a specific time
     *
     * @param  Vehicle|int  $vehicle
     */
    public function isVehicleAvailable($vehicle, ?Carbon $bookingTime = null): bool;
}
