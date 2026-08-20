<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripCollection;
use App\Http\Resources\TripResource;
use App\Services\Driver\DriverService;
use App\Services\Trip\TripService;
// use App\Services\Driver\DriverStatusService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TripManagementController extends Controller
{
    protected $tripService;

    protected $driverService;

    protected $driverStatusService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        TripService $tripService,
        DriverService $driverService,
        DriverService $driverStatusService
    ) {
        $this->tripService = $tripService;
        $this->driverService = $driverService;
        $this->driverStatusService = $driverStatusService;
        $this->middleware(['auth', 'can:manage-trips']);
    }

    /**
     * Display a listing of the trips.
     *
     * @return View|JsonResponse
     */
    public function index(Request $request)
    {
        $trips = $this->tripService->getAllTrips();

        if ($request->wantsJson()) {
            return new TripCollection($trips);
        }

        return view('admin.trips.index', compact('trips'));
    }

    /**
     * Display the specified trip.
     *
     * @return View|JsonResponse
     */
    public function show(Request $request, int $id)
    {
        try {
            $trip = $this->tripService->getTripById($id);

            if ($request->wantsJson()) {
                return new TripResource($trip);
            }

            return view('admin.trips.show', compact('trip'));
        } catch (ModelNotFoundException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Trip not found.',
                ], 404);
            }

            return abort(404);
        }
    }

    /**
     * Assign a driver to a trip.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function assignDriver(Request $request, int $id)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        try {
            DB::beginTransaction();

            $driverId = $request->input('driver_id');

            // Check if driver is available
            $driver = $this->driverService->getDriverById($driverId);
            if ($driver->availability_status !== 'available') {
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'Driver is not available.',
                    ], 422);
                }

                return back()->withErrors([
                    'driver_id' => 'Selected driver is not available.',
                ]);
            }

            // Assign driver to trip using acceptTrip method
            $trip = $this->tripService->acceptTrip($id, $driverId);

            DB::commit();

            if ($request->wantsJson()) {
                return new TripResource($trip);
            }

            return redirect()->route('admin.trips.show', $id)
                ->with('success', 'Driver assigned successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Trip or driver not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign driver to trip: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to assign driver to trip.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to assign driver to trip: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Update trip status.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:pending,accepted,in_progress,completed,cancelled',
        ]);

        try {
            DB::beginTransaction();

            $status = $request->input('status');
            $trip = null;

            // Use the appropriate service method based on the requested status
            switch ($status) {
                case 'accepted':
                    // For accepted status, we need a driver ID
                    $request->validate(['driver_id' => 'required|exists:drivers,id']);
                    $trip = $this->tripService->acceptTrip($id, $request->input('driver_id'));
                    break;

                case 'in_progress':
                    $trip = $this->tripService->startTrip($id);
                    break;

                case 'completed':
                    // For completed status, we need distance information
                    $request->validate(['distance' => 'required|numeric|min:0']);
                    $additionalData = $request->only(['end_location_lat', 'end_location_lng', 'notes']);
                    $trip = $this->tripService->completeTrip($id, $request->input('distance'), $additionalData);
                    break;

                case 'cancelled':
                    $trip = $this->tripService->cancelTrip($id);
                    break;

                default:
                    throw new \Exception('Invalid status transition');
            }

            DB::commit();

            if ($request->wantsJson()) {
                return new TripResource($trip);
            }

            return redirect()->route('admin.trips.show', $id)
                ->with('success', 'Trip status updated successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Trip not found.',
                ], 404);
            }

            return abort(404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update trip status: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to update trip status.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to update trip status: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate trip fare.
     */
    public function calculateFare(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'distance' => 'required|numeric|min:0',
        ]);

        try {
            $fare = $this->tripService->calculateFare(
                $request->input('vehicle_type_id'),
                $request->input('distance')
            );

            return response()->json([
                'fare' => $fare,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to calculate fare.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get trip statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $allTrips = $this->tripService->getAllTrips();

        $statistics = [
            'total_count' => $allTrips->count(),
            'pending_count' => $allTrips->where('status', 'pending')->count(),
            'accepted_count' => $allTrips->where('status', 'accepted')->count(),
            'in_progress_count' => $allTrips->where('status', 'in_progress')->count(),
            'completed_count' => $allTrips->where('status', 'completed')->count(),
            'cancelled_count' => $allTrips->where('status', 'cancelled')->count(),
        ];

        return response()->json($statistics);
    }
}
