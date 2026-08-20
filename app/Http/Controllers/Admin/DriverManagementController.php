<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDriverRequest;
use App\Http\Requests\Admin\UpdateDriverRequest;
use App\Http\Resources\DriverCollection;
use App\Http\Resources\DriverResource;
use App\Services\Driver\DriverService;
use App\Services\Driver\DriverStatusService;
use App\Services\TaxiService\TaxiServiceManagementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DriverManagementController extends Controller
{
    protected $driverService;

    protected $driverStatusService;

    protected $taxiServiceManagementService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        DriverService $driverService,
        DriverStatusService $driverStatusService,
        TaxiServiceManagementService $taxiServiceManagementService
    ) {
        $this->driverService = $driverService;
        $this->driverStatusService = $driverStatusService;
        $this->taxiServiceManagementService = $taxiServiceManagementService;
        $this->middleware(['auth', 'can:manage-drivers']);
    }

    /**
     * Display a listing of the drivers.
     *
     * @return View|JsonResponse
     */
    public function index(Request $request)
    {
        $drivers = $this->driverService->getAllDrivers();

        if ($request->wantsJson()) {
            return new DriverCollection($drivers);
        }

        $taxiServices = $this->taxiServiceManagementService->getAllTaxiServices();

        return view('admin.drivers.index', compact('drivers', 'taxiServices'));
    }

    /**
     * Show the form for creating a new driver.
     */
    public function create(): View
    {
        $taxiServices = $this->taxiServiceManagementService->getAllTaxiServices();

        return view('admin.drivers.create', compact('taxiServices'));
    }

    /**
     * Store a newly created driver in storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function store(StoreDriverRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $driver = $this->driverService->createDriver($data);

            DB::commit();

            if ($request->wantsJson()) {
                return new DriverResource($driver);
            }

            return redirect()->route('admin.drivers.show', $driver->id)
                ->with('success', 'Driver created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create driver: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to create driver.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to create driver: '.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Display the specified driver.
     *
     * @return View|JsonResponse
     */
    public function show(Request $request, int $id)
    {
        try {
            $driver = $this->driverService->getDriverById($id);

            if ($request->wantsJson()) {
                return new DriverResource($driver);
            }

            return view('admin.drivers.show', compact('driver'));
        } catch (ModelNotFoundException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Driver not found.',
                ], 404);
            }

            return abort(404);
        }
    }

    /**
     * Show the form for editing the specified driver.
     */
    public function edit(int $id): View
    {
        $driver = $this->driverService->getDriverById($id);
        $taxiServices = $this->taxiServiceManagementService->getAllTaxiServices();

        return view('admin.drivers.edit', compact('driver', 'taxiServices'));
    }

    /**
     * Update the specified driver in storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function update(UpdateDriverRequest $request, int $id)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $result = $this->driverService->updateDriver($id, $data);

            if (! $result) {
                throw new \Exception('Failed to update driver');
            }

            $driver = $this->driverService->getDriverById($id);

            DB::commit();

            if ($request->wantsJson()) {
                return new DriverResource($driver);
            }

            return redirect()->route('admin.drivers.show', $id)
                ->with('success', 'Driver updated successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Driver not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update driver: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to update driver.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to update driver: '.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Remove the specified driver from storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function destroy(Request $request, int $id)
    {
        try {
            DB::beginTransaction();

            $result = $this->driverService->deleteDriver($id);

            if (! $result) {
                throw new \Exception('Failed to delete driver');
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Driver deleted successfully.',
                ]);
            }

            return redirect()->route('admin.drivers.index')
                ->with('success', 'Driver deleted successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Driver not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete driver: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to delete driver.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to delete driver: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Update driver availability status.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:available,busy,offline',
        ]);

        try {
            DB::beginTransaction();

            $status = $request->input('status');
            $result = false;

            switch ($status) {
                case 'available':
                    $result = $this->driverStatusService->markDriverAvailable($id);
                    break;
                case 'busy':
                    $result = $this->driverStatusService->markDriverBusy($id);
                    break;
                case 'offline':
                    $result = $this->driverStatusService->markDriverOffline($id);
                    break;
                default:
                    throw new \Exception('Invalid status');
            }

            if (! $result) {
                throw new \Exception('Failed to update driver status');
            }

            $driver = $this->driverService->getDriverById($id);

            DB::commit();

            if ($request->wantsJson()) {
                return new DriverResource($driver);
            }

            return redirect()->route('admin.drivers.show', $id)
                ->with('success', 'Driver status updated successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Driver not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update driver status: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to update driver status.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to update driver status: '.$e->getMessage(),
            ]);
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
            $result = $this->driverService->updateDriverLocation(
                $id,
                $request->input('latitude'),
                $request->input('longitude')
            );

            if (! $result) {
                throw new \Exception('Failed to update driver location');
            }

            return response()->json([
                'message' => 'Driver location updated successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Driver not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update driver location: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to update driver location.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available drivers for a specific taxi service.
     */
    public function getAvailableDrivers(Request $request, int $taxiServiceId): JsonResponse
    {
        try {
            $drivers = $this->driverService->getDriversByTaxiService($taxiServiceId);
            $availableDrivers = $drivers->filter(function ($driver) {
                return $driver->availability_status === 'available';
            });

            return response()->json([
                'data' => DriverResource::collection($availableDrivers),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get available drivers: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to get available drivers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
