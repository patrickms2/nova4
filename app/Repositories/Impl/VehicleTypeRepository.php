<?php

namespace App\Repositories\Impl;

use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VehicleTypeRepository
{
    /**
     * Get all vehicle types
     */
    public function all(): Collection
    {
        return VehicleType::with(['taxiService'])->get();
    }

    /**
     * Get a vehicle type by ID or fail
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): VehicleType
    {
        return VehicleType::with(['taxiService'])->findOrFail($id);
    }

    /**
     * Get active vehicle types
     */
    public function getActive(): Collection
    {
        return VehicleType::where('is_active', true)
            ->with(['taxiService'])
            ->get();
    }

    /**
     * Get vehicle types by taxi service
     */
    public function getByTaxiService(int $taxiServiceId): Collection
    {
        return VehicleType::where('taxi_service_id', $taxiServiceId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Create a new vehicle type
     */
    public function create(array $data): VehicleType
    {
        return VehicleType::create($data);
    }

    /**
     * Update a vehicle type
     */
    public function update(int $id, array $data): bool
    {
        return VehicleType::where('id', $id)->update($data);
    }

    /**
     * Delete a vehicle type
     */
    public function delete(int $id): bool
    {
        return VehicleType::where('id', $id)->delete();
    }
}
