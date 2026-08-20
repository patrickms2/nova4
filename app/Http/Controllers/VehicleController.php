<?php

namespace App\Http\Controllers;

use App\Http\Requests\Vehicle\ByTaxiServiceRequest;
use App\Http\Requests\Vehicle\DeleteVehicleRequest;
use App\Http\Requests\Vehicle\ShowVehicleRequest;
use App\Http\Requests\Vehicle\StoreVehicleRequest;
use App\Http\Requests\Vehicle\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\Vehicle\VehicleService;
use App\Services\Vehicle\VehicleTypeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleController extends Controller
{
    protected $vehicleService;

    protected $vehicleTypeService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(VehicleService $vehicleService, VehicleTypeService $vehicleTypeService)
    {
        $this->vehicleService = $vehicleService;
        $this->vehicleTypeService = $vehicleTypeService;
    }

    /**
     * Display a listing of the vehicles.
     */
    public function index(): JsonResponse
    {
        try {
            $vehicles = $this->vehicleService->getAllVehicles();

            return response()->json([
                'success' => true,
                'data' => VehicleResource::collection($vehicles),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve vehicles: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve vehicles',
            ], 500);
        }
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validate vehicle type exists
            $this->vehicleTypeService->getVehicleTypeById($request->vehicle_type_id);

            $data = $request->validated();
            $vehicle = $this->vehicleService->createVehicle($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle created successfully',
                'data' => new VehicleResource($vehicle),
            ], 201);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Vehicle type not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create vehicle: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to create vehicle',
            ], 500);
        }
    }

    /**
     * Display the specified vehicle.
     *
     * @param  ShowVehicleRequest  $request
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $vehicle = $this->vehicleService->getVehicleById($request->id);

            return response()->json([
                'success' => true,
                'data' => new VehicleResource($vehicle),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Vehicle not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve vehicle: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve vehicle',
            ], 500);
        }
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(UpdateVehicleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if vehicle exists
            $this->vehicleService->getVehicleById($request->id);

            // If vehicle type is being updated, validate it exists
            if ($request->has('vehicle_type_id')) {
                $this->vehicleTypeService->getVehicleTypeById($request->vehicle_type_id);
            }

            $data = $request->validated();
            $vehicle = $this->vehicleService->updateVehicle($request->id, $data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully',
                'data' => new VehicleResource($vehicle),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Vehicle or vehicle type not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update vehicle: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to update vehicle',
            ], 500);
        }
    }

    /**
     * Remove the specified vehicle from storage.
     *
     * @param  DeleteVehicleRequest  $request
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if vehicle exists
            $this->vehicleService->getVehicleById($request->id);

            // Delete the vehicle using service
            $result = $this->vehicleService->deleteVehicle($request->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Vehicle not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete vehicle: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete vehicle',
            ], 500);
        }
    }

    /**
     * Get vehicles by taxi service.
     *
     * @param  ByTaxiServiceRequest  $request
     */
    public function getByTaxiService(Request $request): JsonResponse
    {
        try {
            // Get vehicles by taxi service using service
            $vehicles = $this->vehicleService->getVehiclesByTaxiService($request->taxi_service_id);

            return response()->json([
                'success' => true,
                'data' => VehicleResource::collection($vehicles),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve vehicles by taxi service: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve vehicles',
            ], 500);
        }
    }

    /**
     * Get vehicles by type.
     */
    public function getByType(Request $request): JsonResponse
    {
        try {
            // Ensure the vehicle type exists
            $this->vehicleTypeService->getVehicleTypeById($request->type_id);

            // Get vehicles by type using service
            $vehicles = $this->vehicleService->getVehiclesByType($request->type_id);

            return response()->json([
                'success' => true,
                'data' => VehicleResource::collection($vehicles),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Vehicle type not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve vehicles by type: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve vehicles',
            ], 500);
        }
    }

    /**
     * Get available vehicles for booking.
     */
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'taxi_service_id' => 'required|integer|exists:taxi_services,id',
            'vehicle_type_id' => 'required|integer|exists:vehicle_types,id',
            'booking_time' => 'required|date_format:Y-m-d H:i:s|after:now',
        ]);

        try {
            // Get available vehicles using service
            $vehicles = $this->vehicleService->getAvailableVehicles(
                $request->input('taxi_service_id'),
                $request->input('vehicle_type_id'),
                $request->input('booking_time')
            );

            return response()->json([
                'success' => true,
                'data' => VehicleResource::collection($vehicles),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve available vehicles: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve available vehicles',
            ], 500);
        }
    }
}
