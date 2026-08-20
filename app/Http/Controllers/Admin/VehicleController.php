<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleRequest;
use App\Http\Requests\Admin\UpdateVehicleRequest;
use App\Http\Resources\VehicleCollection;
use App\Http\Resources\VehicleResource;
use App\Services\TaxiService\TaxiServiceManagementService;
use App\Services\Vehicle\VehicleService;
use App\Services\Vehicle\VehicleTypeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VehicleController extends Controller
{
    protected $vehicleService;

    protected $vehicleTypeService;

    protected $taxiServiceManagementService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        VehicleService $vehicleService,
        VehicleTypeService $vehicleTypeService,
        TaxiServiceManagementService $taxiServiceManagementService
    ) {
        $this->vehicleService = $vehicleService;
        $this->vehicleTypeService = $vehicleTypeService;
        $this->taxiServiceManagementService = $taxiServiceManagementService;
        $this->middleware(['auth', 'can:manage-vehicles']);
    }

    /**
     * Display a listing of the vehicles.
     *
     * @return View|JsonResponse
     */
    public function index(Request $request)
    {
        $vehicles = $this->vehicleService->getAllVehicles();

        if ($request->wantsJson()) {
            return new VehicleCollection($vehicles);
        }

        $taxiServices = $this->taxiServiceManagementService->getAllTaxiServices();
        $vehicleTypes = $this->vehicleTypeService->getAllVehicleTypes();

        return view('admin.vehicles.index', compact('vehicles', 'taxiServices', 'vehicleTypes'));
    }

    /**
     * Show the form for creating a new vehicle.
     */
    public function create(): View
    {
        $taxiServices = $this->taxiServiceManagementService->getAllTaxiServices();
        $vehicleTypes = $this->vehicleTypeService->getAllVehicleTypes();

        return view('admin.vehicles.create', compact('taxiServices', 'vehicleTypes'));
    }

    /**
     * Store a newly created vehicle in storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function store(StoreVehicleRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $vehicle = $this->vehicleService->createVehicle($data);

            DB::commit();

            if ($request->wantsJson()) {
                return new VehicleResource($vehicle);
            }

            return redirect()->route('admin.vehicles.show', $vehicle->id)
                ->with('success', 'Vehicle created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create vehicle: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to create vehicle.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to create vehicle: '.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Display the specified vehicle.
     *
     * @return View|JsonResponse
     */
    public function show(Request $request, int $id)
    {
        try {
            $vehicle = $this->vehicleService->getVehicleById($id);

            if ($request->wantsJson()) {
                return new VehicleResource($vehicle);
            }

            return view('admin.vehicles.show', compact('vehicle'));
        } catch (ModelNotFoundException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vehicle not found.',
                ], 404);
            }

            return abort(404);
        }
    }

    /**
     * Show the form for editing the specified vehicle.
     */
    public function edit(int $id): View
    {
        $vehicle = $this->vehicleService->getVehicleById($id);
        $taxiServices = $this->taxiServiceManagementService->getAllTaxiServices();
        $vehicleTypes = $this->vehicleTypeService->getAllVehicleTypes();

        return view('admin.vehicles.edit', compact('vehicle', 'taxiServices', 'vehicleTypes'));
    }

    /**
     * Update the specified vehicle in storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function update(UpdateVehicleRequest $request, int $id)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $vehicle = $this->vehicleService->updateVehicle($id, $data);

            DB::commit();

            if ($request->wantsJson()) {
                return new VehicleResource($vehicle);
            }

            return redirect()->route('admin.vehicles.show', $id)
                ->with('success', 'Vehicle updated successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vehicle not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update vehicle: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to update vehicle.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to update vehicle: '.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Remove the specified vehicle from storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function destroy(Request $request, int $id)
    {
        try {
            DB::beginTransaction();

            $result = $this->vehicleService->deleteVehicle($id);

            if (! $result) {
                throw new \Exception('Failed to delete vehicle');
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vehicle deleted successfully.',
                ]);
            }

            return redirect()->route('admin.vehicles.index')
                ->with('success', 'Vehicle deleted successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vehicle not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete vehicle: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to delete vehicle.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to delete vehicle: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Get vehicles by taxi service.
     */
    public function getByTaxiService(Request $request, int $taxiServiceId): JsonResponse
    {
        try {
            $vehicles = $this->vehicleService->getVehiclesByTaxiService($taxiServiceId);

            return response()->json([
                'data' => VehicleResource::collection($vehicles),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get vehicles by taxi service: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to get vehicles by taxi service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get vehicles by vehicle type.
     */
    public function getByVehicleType(Request $request, int $vehicleTypeId): JsonResponse
    {
        try {
            $vehicles = $this->vehicleService->getVehiclesByType($vehicleTypeId);

            return response()->json([
                'data' => VehicleResource::collection($vehicles),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get vehicles by vehicle type: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to get vehicles by vehicle type.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available vehicles for booking.
     */
    public function getAvailableVehicles(Request $request): JsonResponse
    {
        $request->validate([
            'taxi_service_id' => 'required|exists:taxi_services,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'booking_datetime' => 'required|date',
        ]);

        try {
            $vehicles = $this->vehicleService->getAvailableVehicles(
                $request->input('taxi_service_id'),
                $request->input('vehicle_type_id'),
                $request->input('booking_datetime')
            );

            return response()->json([
                'data' => VehicleResource::collection($vehicles),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get available vehicles: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to get available vehicles.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
