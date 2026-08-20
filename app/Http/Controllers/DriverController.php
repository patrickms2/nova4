<?php

namespace App\Http\Controllers;

use App\Http\Requests\Driver\StoreDriverRequest;
use App\Http\Requests\Driver\UpdateDriverRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Services\Driver\DriverService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    protected $driverService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        DriverService $driverService,
    ) {
        $this->driverService = $driverService;
        $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $drivers = $this->driverService->getAllDrivers();

            return response()->json([
                'success' => true,
                'data' => DriverResource::collection($drivers),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve drivers: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve drivers',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreDriverRequest  $request
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $driver = $this->driverService->createDriver($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Driver created successfully',
                'data' => new DriverResource($driver),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create driver: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to create driver',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $driver = $this->driverService->getDriverById($id);

            return response()->json([
                'success' => true,
                'data' => new DriverResource($driver),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Driver not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve driver: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve driver',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateDriverRequest  $request
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if driver exists
            $this->driverService->getDriverById($id);

            $data = $request->validated();
            $result = $this->driverService->updateDriver($id, $data);

            // Get updated driver
            $driver = $this->driverService->getDriverById($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Driver updated successfully',
                'data' => new DriverResource($driver),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Driver not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update driver: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to update driver',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if driver exists
            $this->driverService->getDriverById($id);

            // Delete the driver using service
            $result = $this->driverService->deleteDriver($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Driver deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Driver not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete driver: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete driver',
            ], 500);
        }
    }

    /**
     * Get drivers by taxi service.
     */
    public function getByTaxiService(int $taxiServiceId): JsonResponse
    {
        try {
            $drivers = $this->driverService->getDriversByTaxiService($taxiServiceId);

            return response()->json([
                'success' => true,
                'data' => DriverResource::collection($drivers),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve drivers by taxi service: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve drivers',
            ], 500);
        }
    }

    /**
     * Get available drivers.
     */
    public function getAvailableDrivers(): JsonResponse
    {
        try {
            $drivers = $this->driverService->getAvailableDrivers();

            return response()->json([
                'success' => true,
                'data' => DriverResource::collection($drivers),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve available drivers: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve available drivers',
            ], 500);
        }
    }

    /**
     * Update driver rating.
     *
     * @param  int  $id
     * @return Response
     */
    public function updateRating(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $driver = $this->driverService->updateDriver($id, $request->rating);

            return response()->json(['data' => $driver, 'message' => 'Driver rating updated successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['errors' => ['Driver not found']], 404);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['Failed to update driver rating: '.$e->getMessage()]], 500);
        }
    }

    /**
     * Update driver location.
     */
    public function updateLocation(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            // Check if driver exists
            $this->driverService->getDriverById($id);

            // Update driver location using service
            $result = $this->driverService->updateDriverLocation(
                $id,
                $request->input('latitude'),
                $request->input('longitude')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Driver location updated successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Driver not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update driver location: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to update driver location',
            ], 500);
        }
    }

    /**
     * Update driver availability status.
     */
    public function updateAvailabilityStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:available,busy,offline',
        ]);

        try {
            DB::beginTransaction();

            // Check if driver exists
            $this->driverService->getDriverById($id);

            // Update driver status based on requested status
            $status = $request->input('status');
            $result = false;

            switch ($status) {
                case 'available':
                    $result = $this->driverService->markAvailable($id);
                    break;
                case 'busy':
                    $result = $this->driverService->markBusy($id);
                    break;
                case 'offline':
                    $result = $this->driverService->markOffline($id);
                    break;
            }

            if (! $result) {
                throw new \Exception('Failed to update driver status');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Driver availability status updated successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Driver not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update driver availability status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to update driver availability status',
            ], 500);
        }
    }
}
