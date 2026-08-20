<?php

namespace App\Services\Vehicle;

use App\Events\VehicleTypeUpdated;
use App\Models\VehicleType;
use App\Repositories\Impl\VehicleTypeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleTypeService
{
    protected $vehicleTypeRepository;

    /**
     * Create a new service instance.
     *
     * @return void
     */
    public function __construct(VehicleTypeRepository $vehicleTypeRepository)
    {
        $this->vehicleTypeRepository = $vehicleTypeRepository;
    }

    /**
     * Get all vehicle types
     */
    public function getAllVehicleTypes(): Collection
    {
        return $this->vehicleTypeRepository->all();
    }

    /**
     * Get a vehicle type by ID
     *
     * @throws ModelNotFoundException
     */
    public function getVehicleTypeById(int $id): VehicleType
    {
        return $this->vehicleTypeRepository->findOrFail($id);
    }

    /**
     * Get vehicle types by taxi service
     */
    public function getVehicleTypesByTaxiService(int $taxiServiceId): Collection
    {
        return $this->vehicleTypeRepository->getByTaxiService($taxiServiceId);
    }

    /**
     * Create a new vehicle type
     */
    public function createVehicleType(array $data): VehicleType
    {
        try {
            DB::beginTransaction();

            $vehicleType = $this->vehicleTypeRepository->create($data);

            DB::commit();

            return $vehicleType;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create vehicle type: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a vehicle type
     */
    public function updateVehicleType(int $id, array $data): bool
    {
        try {
            DB::beginTransaction();

            $result = $this->vehicleTypeRepository->update($id, $data);

            // Get the updated vehicle type and broadcast the event
            if ($result) {
                $vehicleType = $this->vehicleTypeRepository->findOrFail($id);
                event(new VehicleTypeUpdated($vehicleType));
            }

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update vehicle type: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a vehicle type
     */
    public function deleteVehicleType(int $id): bool
    {
        try {
            DB::beginTransaction();

            $result = $this->vehicleTypeRepository->delete($id);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete vehicle type: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle vehicle type active status
     */
    public function toggleActiveStatus(int $id): bool
    {
        try {
            $vehicleType = $this->getVehicleTypeById($id);

            return $this->updateVehicleType($id, ['is_active' => ! $vehicleType->is_active]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle vehicle type status: '.$e->getMessage());
            throw $e;
        }
    }
}
