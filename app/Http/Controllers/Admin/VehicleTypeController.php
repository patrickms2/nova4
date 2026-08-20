<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleTypeRequest;
use App\Http\Requests\Admin\UpdateVehicleTypeRequest;
use App\Http\Resources\VehicleTypeCollection;
use App\Http\Resources\VehicleTypeResource;
use App\Services\TaxiService\TaxiServiceManagementService;
use App\Services\Vehicle\VehicleTypeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VehicleTypeController extends Controller
{
    protected $vehicleTypeService;

    protected $taxiServiceManagementService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        VehicleTypeService $vehicleTypeService,
        TaxiServiceManagementService $taxiServiceManagementService
    ) {
        $this->vehicleTypeService = $vehicleTypeService;
        $this->taxiServiceManagementService = $taxiServiceManagementService;
        $this->middleware(['auth', 'can:manage-vehicle-types']);
    }

    /**
     * Display a listing of the vehicle types.
     *
     * @return View|JsonResponse
     */
    public function index(Request $request)
    {
        $vehicleTypes = $this->vehicleTypeService->getAllVehicleTypes();

        if ($request->wantsJson()) {
            return new VehicleTypeCollection($vehicleTypes);
        }

        $taxiServices = $this->taxiServiceManagementService->getAllTaxiServices();

        return view('admin.vehicle-types.index', compact('vehicleTypes', 'taxiServices'));
    }

    /**
     * Show the form for creating a new vehicle type.
     */
    public function create(): View
    {
        $taxiServices = $this->taxiServiceManagementService->getAllTaxiServices();

        return view('admin.vehicle-types.create', compact('taxiServices'));
    }

    /**
     * Store a newly created vehicle type in storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function store(StoreVehicleTypeRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $vehicleType = $this->vehicleTypeService->createVehicleType($data);

            DB::commit();

            if ($request->wantsJson()) {
                return new VehicleTypeResource($vehicleType);
            }

            return redirect()->route('admin.vehicle-types.show', $vehicleType->id)
                ->with('success', 'Vehicle type created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create vehicle type: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to create vehicle type.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to create vehicle type: '.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Display the specified vehicle type.
     *
     * @return View|JsonResponse
     */
    public function show(Request $request, int $id)
    {
        try {
            $vehicleType = $this->vehicleTypeService->getVehicleTypeById($id);

            if ($request->wantsJson()) {
                return new VehicleTypeResource($vehicleType);
            }

            return view('admin.vehicle-types.show', compact('vehicleType'));
        } catch (ModelNotFoundException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vehicle type not found.',
                ], 404);
            }

            return abort(404);
        }
    }

    /**
     * Show the form for editing the specified vehicle type.
     */
    public function edit(int $id): View
    {
        $vehicleType = $this->vehicleTypeService->getVehicleTypeById($id);
        $taxiServices = $this->taxiServiceManagementService->getAllTaxiServices();

        return view('admin.vehicle-types.edit', compact('vehicleType', 'taxiServices'));
    }

    /**
     * Update the specified vehicle type in storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function update(UpdateVehicleTypeRequest $request, int $id)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $result = $this->vehicleTypeService->updateVehicleType($id, $data);

            if (! $result) {
                throw new \Exception('Failed to update vehicle type');
            }

            $vehicleType = $this->vehicleTypeService->getVehicleTypeById($id);

            DB::commit();

            if ($request->wantsJson()) {
                return new VehicleTypeResource($vehicleType);
            }

            return redirect()->route('admin.vehicle-types.show', $id)
                ->with('success', 'Vehicle type updated successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vehicle type not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update vehicle type: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to update vehicle type.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to update vehicle type: '.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Remove the specified vehicle type from storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function destroy(Request $request, int $id)
    {
        try {
            DB::beginTransaction();

            $result = $this->vehicleTypeService->deleteVehicleType($id);

            if (! $result) {
                throw new \Exception('Failed to delete vehicle type');
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vehicle type deleted successfully.',
                ]);
            }

            return redirect()->route('admin.vehicle-types.index')
                ->with('success', 'Vehicle type deleted successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vehicle type not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete vehicle type: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to delete vehicle type.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to delete vehicle type: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Toggle vehicle type active status.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function toggleActiveStatus(Request $request, int $id)
    {
        try {
            DB::beginTransaction();

            $result = $this->vehicleTypeService->toggleActiveStatus($id);

            if (! $result) {
                throw new \Exception('Failed to toggle vehicle type status');
            }

            $vehicleType = $this->vehicleTypeService->getVehicleTypeById($id);

            DB::commit();

            if ($request->wantsJson()) {
                return new VehicleTypeResource($vehicleType);
            }

            return redirect()->route('admin.vehicle-types.show', $id)
                ->with('success', 'Vehicle type status updated successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vehicle type not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to toggle vehicle type status: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to toggle vehicle type status.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to toggle vehicle type status: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Get vehicle types by taxi service.
     */
    public function getByTaxiService(Request $request, int $taxiServiceId): JsonResponse
    {
        try {
            $vehicleTypes = $this->vehicleTypeService->getVehicleTypesByTaxiService($taxiServiceId);

            return response()->json([
                'data' => VehicleTypeResource::collection($vehicleTypes),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get vehicle types by taxi service: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to get vehicle types by taxi service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
