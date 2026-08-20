<?php

namespace App\Http\Controllers;

use App\Http\Resources\VehicleResource;
use App\Services\Vehicle\VehicleTypeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VehicleTypeController extends Controller
{
    protected $vehicleTypeService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(VehicleTypeService $vehicleTypeService)
    {
        $this->vehicleTypeService = $vehicleTypeService;
        // $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the vehicle types.
     */
    public function index(): JsonResponse
    {
        try {
            $vehicleTypes = $this->vehicleTypeService->getAllVehicleTypes();

            return response()->json([
                'success' => true,
                'data' => VehicleResource::collection($vehicleTypes),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve vehicle types: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve vehicle types',
            ], 500);
        }
    }

    /**
     * Store a newly created vehicle type in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'TaxiServiceID' => 'required|exists:TaxiServices,TaxiServiceID',
            'TypeName' => 'required|string|max:255',
            'Description' => 'nullable|string',
            'MaxPassengers' => 'required|integer|min:1',
            'PricePerKm' => 'required|numeric|min:0',
            'BasePrice' => 'required|numeric|min:0',
            'ImageURL' => 'nullable|string',
            'IsActive' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $vehicleType = $this->vehicleTypeService->createVehicleType($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle type created successfully',
                'data' => new VehicleResource($vehicleType),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create vehicle type: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to create vehicle type',
            ], 500);
        }
    }

    /**
     * Display the specified vehicle type.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $vehicleType = $this->vehicleTypeService->getVehicleTypeById($id);

            return response()->json([
                'success' => true,
                'data' => new VehicleResource($vehicleType),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Vehicle type not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve vehicle type: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve vehicle type',
            ], 500);
        }
    }

    /**
     * Update the specified vehicle type in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'TaxiServiceID' => 'sometimes|required|exists:TaxiServices,TaxiServiceID',
            'TypeName' => 'sometimes|required|string|max:255',
            'Description' => 'nullable|string',
            'MaxPassengers' => 'sometimes|required|integer|min:1',
            'PricePerKm' => 'sometimes|required|numeric|min:0',
            'BasePrice' => 'sometimes|required|numeric|min:0',
            'ImageURL' => 'nullable|string',
            'IsActive' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Check if vehicle type exists
            $this->vehicleTypeService->getVehicleTypeById($id);

            // Update vehicle type
            $result = $this->vehicleTypeService->updateVehicleType($id, $request->all());

            // Get updated vehicle type
            $vehicleType = $this->vehicleTypeService->getVehicleTypeById($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle type updated successfully',
                'data' => new VehicleResource($vehicleType),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Vehicle type not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update vehicle type: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to update vehicle type',
            ], 500);
        }
    }

    /**
     * Remove the specified vehicle type from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if vehicle type exists
            $this->vehicleTypeService->getVehicleTypeById($id);

            // Delete vehicle type
            $result = $this->vehicleTypeService->deleteVehicleType($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle type deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Vehicle type not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete vehicle type: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete vehicle type',
            ], 500);
        }
    }

    /**
     * Toggle vehicle type active status.
     */
    public function toggleActiveStatus(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if vehicle type exists
            $this->vehicleTypeService->getVehicleTypeById($id);

            // Toggle active status using service
            $result = $this->vehicleTypeService->toggleActiveStatus($id);

            // Get updated vehicle type
            $vehicleType = $this->vehicleTypeService->getVehicleTypeById($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle type status toggled successfully',
                'data' => new VehicleResource($vehicleType),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Vehicle type not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to toggle vehicle type status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to toggle vehicle type status',
            ], 500);
        }
    }

    /**
     * Get vehicle types by taxi service.
     */
    public function getVehicleTypesByTaxiService(int $taxiServiceId): JsonResponse
    {
        if (empty($taxiServiceId)) {
            return response()->json([
                'success' => false,
                'error' => 'Taxi service ID is required',
            ], 400);
        }

        try {
            $vehicleTypes = $this->vehicleTypeService->getVehicleTypesByTaxiService($taxiServiceId);

            if ($vehicleTypes->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No vehicle types found for this taxi service',
                    'data' => [],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => VehicleResource::collection($vehicleTypes),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve vehicle types: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve vehicle types',
            ], 500);
        }
    }
}
