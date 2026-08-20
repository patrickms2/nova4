<?php

namespace App\Services\interfaces;

use App\Models\DriverVehicleAssignment;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface DriverVehicleAssignmentServiceInterface
{
    /**
     * Find active assignment for driver and vehicle
     */
    public function findActive(int $driverId, int $vehicleId): ?DriverVehicleAssignment;

    /**
     * Assign a vehicle to a driver
     *
     * @throws \Exception
     */
    public function assign(int $driverId, int $vehicleId): DriverVehicleAssignment;

    /**
     * Unassign a vehicle from a driver
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function unassign(int $assignmentId): void;

    /**
     * List active assignments by driver
     */
    public function listActiveByDriver(int $driverId): Collection;

    /**
     * List active assignments by vehicle
     */
    public function listActiveByVehicle(int $vehicleId): Collection;

    /**
     * Get assignment history with filters
     */
    public function history(array $filters = [], int $perPage = 15): Paginator;

    /**
     * Check if driver is available for assignment
     */
    public function checkDriverAvailable(int $driverId): bool;

    /**
     * Check if vehicle is available for assignment
     */
    public function checkVehicleAvailable(int $vehicleId): bool;

    /**
     * End all active assignments for a driver
     */
    public function endAllForDriver(int $driverId): int;

    /**
     * End all active assignments for a vehicle
     */
    public function endAllForVehicle(int $vehicleId): int;

    /**
     * Get assignment by ID
     */
    public function getById(int $assignmentId): ?DriverVehicleAssignment;

    /**
     * Get active drivers for a vehicle
     */
    public function driversForVehicle(int $vehicleId): Collection;

    /**
     * Get active vehicles for a driver
     */
    public function vehiclesForDriver(int $driverId): Collection;
}
