<?php

namespace App\Services\interfaces;

use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface VehicleTypeServiceInterface
{
    /**
     * Get all vehicle types
     */
    public function getAllVehicleTypes(): Collection;

    /**
     * Get a vehicle type by ID
     *
     * @throws ModelNotFoundException
     */
    public function getVehicleTypeById(int $id): VehicleType;

    /**
     * Get vehicle types by taxi service
     */
    public function getVehicleTypesByTaxiService(int $taxiServiceId): Collection;

    /**
     * Create a new vehicle type
     *
     * @throws \Exception
     */
    public function createVehicleType(array $data): VehicleType;

    /**
     * Update a vehicle type
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function updateVehicleType(int $id, array $data): VehicleType;

    /**
     * Delete a vehicle type
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function deleteVehicleType(int $id): bool;

    /**
     * Toggle active status of a vehicle type
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function toggleVehicleTypeStatus(int $id): VehicleType;
}
