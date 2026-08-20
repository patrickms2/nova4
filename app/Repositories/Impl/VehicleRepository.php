<?php

namespace App\Repositories\Impl;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VehicleRepository
{
    /**
     * Get all vehicles
     */
    public function all(): Collection
    {
        return Vehicle::with(['taxiService', 'vehicleType'])->get();
    }

    /**
     * Get a vehicle by ID or fail
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Vehicle
    {
        return Vehicle::with(['taxiService', 'vehicleType'])->findOrFail($id);
    }

    /**
     * Get active vehicles
     */
    public function getActive(): Collection
    {
        return Vehicle::where('is_active', true)
            ->with(['taxiService', 'vehicleType'])
            ->get();
    }

    /**
     * Get vehicles by taxi service
     */
    public function getByTaxiService(int $taxiServiceId): Collection
    {
        return Vehicle::where('taxi_service_id', $taxiServiceId)
            ->where('is_active', true)
            ->with(['vehicleType'])
            ->get();
    }

    /**
     * Get vehicles by vehicle type
     */
    public function getByVehicleType(int $vehicleTypeId): Collection
    {
        return Vehicle::with(['vehicleType'])
            ->where('vehicle_type_id', $vehicleTypeId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Create a new vehicle
     */
    public function create(array $data): Vehicle
    {
        $type = $this->findOrFail($data['vehicle_type_id']);
        if ($type->taxi_service_id !== $data['taxi_service_id']) {
            throw new \InvalidArgumentException('Vehicle type doesn\'t belong to this taxi service');
        }

        return Vehicle::create($data);
    }

    /**
     * Update a vehicle
     *
     * @return bool
     */
    public function update(int $id, array $data): Vehicle
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update($data);

        return $vehicle->fresh();
    }

    /**
     * Delete a vehicle
     */
    public function delete(int $id): bool
    {
        return Vehicle::where('id', $id)->delete();
    }

    /**
     * Get vehicles by taxi service and vehicle type
     */
    public function getByTaxiServiceAndType(int $taxiServiceId, int $vehicleTypeId): Collection
    {
        return Vehicle::where('taxi_service_id', $taxiServiceId)
            ->where('vehicle_type_id', $vehicleTypeId)
            ->where('is_active', true)
            ->with(['vehicleType'])
            ->get();
    }
}
