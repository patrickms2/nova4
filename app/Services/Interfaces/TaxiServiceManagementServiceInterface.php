<?php

namespace App\Services\interfaces;

use App\Models\TaxiService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaxiServiceManagementServiceInterface
{
    // #              For Super Admin
    /**
     * Get all taxi services
     */
    public function getAllTaxiServices(): Collection;

    /**
     * Get paginated taxi services
     */
    public function getPaginatedTaxiServices(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get active taxi services
     */
    public function getActiveTaxiServices(): Collection;

    // #                   For Admin After the super admin adds him
    /**
     * Get a taxi service by ID
     *
     * @throws ModelNotFoundException
     */
    public function getTaxiServiceById(int $id): TaxiService;

    /**
     * Create a new taxi service
     *
     * @throws \Exception
     */
    public function createTaxiService(array $data): TaxiService;

    /**
     * Update a taxi service
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function updateTaxiService(int $id, array $data): TaxiService;

    /**
     * Delete a taxi service
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function deleteTaxiService(int $id): bool;

    // #                   USER
    /**
     * Update taxi service rating
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function updateTaxiServiceRating(int $id, float $rating): TaxiService;

    // #                  NEARBY TAXI SERVICES FROM THE USER
    /**
     * Get taxi services by location
     */
    public function getTaxiServicesByLocation(float $lat, float $lng, float $radius = 10.0): Collection;

    /**
     * Get full taxi service details with relationships
     *
     * @throws ModelNotFoundException
     */
    public function getFullTaxiServiceDetails(int $id): TaxiService;
}
