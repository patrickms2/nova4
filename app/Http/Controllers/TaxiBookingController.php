<?php

namespace App\Http\Controllers;

use App\Exceptions\NoDriversAvailableException;
use App\Http\Requests\TaxiBooking\StoreTaxiBookingRequest;
use App\Http\Resources\TaxiBookingResource;
use App\Services\TaxiBooking\TaxiBookingService;
use App\Services\TaxiService\TaxiServiceManagementService;
use App\Services\Vehicle\VehicleTypeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaxiBookingController extends Controller
{
    protected $taxiBookingService;

    protected $taxiServiceManagementService;

    protected $vehicleTypeService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        TaxiBookingService $taxiBookingService,
        TaxiServiceManagementService $taxiServiceManagementService,
        VehicleTypeService $vehicleTypeService
    ) {
        $this->taxiBookingService = $taxiBookingService;
        $this->taxiServiceManagementService = $taxiServiceManagementService;
        $this->vehicleTypeService = $vehicleTypeService;
        $this->middleware('auth');
    }

    /**
     * Display a listing of the user's taxi bookings.
     */
    public function index(): JsonResponse
    {
        try {
            $bookingId = Auth::user()->booking_id; // Assuming user has a booking relationship
            $taxiBookings = $this->taxiBookingService->getTaxiBookingById($bookingId);

            return response()->json([
                'success' => true,
                'data' => TaxiBookingResource::collection($taxiBookings),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user taxi bookings: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve taxi bookings',
            ], 500);
        }
    }

    /**
     * Store a newly created taxi booking in storage.
     */
    public function store(StoreTaxiBookingRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['user_id'] = Auth::id();

            // Book a taxi with driver assignment using service method
            $taxiBooking = $this->taxiBookingService->bookTaxi(
                $data['taxi_service_id'],
                $data['pickup_time'],
                $data['pickup_location']['Latitude'],
                $data['pickup_location']['Longitude'],
                $data['radius'],
                $data
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Taxi booking created successfully',
                'data' => new TaxiBookingResource($taxiBooking),
            ], 201);
        } catch (NoDriversAvailableException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'No drivers available for this booking',
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create taxi booking: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to create taxi booking: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified taxi booking.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $taxiBooking = $this->taxiBookingService->getTaxiBookingById($id);

            // Check if the booking belongs to the authenticated user
            if ($taxiBooking->booking->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new TaxiBookingResource($taxiBooking),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Taxi booking not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve taxi booking: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve taxi booking',
            ], 500);
        }
    }

    /**
     * Cancel a taxi booking.
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $taxiBooking = $this->taxiBookingService->getTaxiBookingById($id);

            // Check if the booking belongs to the authenticated user
            if ($taxiBooking->booking->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            // Update the booking status to cancelled using service method
            $updatedBooking = $this->taxiBookingService->updateTaxiBooking($id, [
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Taxi booking cancelled successfully',
                'data' => new TaxiBookingResource($updatedBooking),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Taxi booking not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel taxi booking: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel taxi booking: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available taxi services for a location.
     */
    public function getAvailableTaxiServices(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            // Get the nearest location's ID
            $locationId = $this->getNearestLocationId(
                $request->input('latitude'),
                $request->input('longitude')
            );

            // Use service method to get taxi services by location
            $taxiServices = $this->taxiServiceManagementService->getTaxiServicesByLocation($locationId);

            return response()->json([
                'success' => true,
                'data' => $taxiServices,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get available taxi services: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to get available taxi services: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available vehicle types for a taxi service.
     */
    public function getAvailableVehicleTypes(int $taxiServiceId): JsonResponse
    {
        try {
            // Use service method to get vehicle types by taxi service
            $vehicleTypes = $this->vehicleTypeService->getVehicleTypesByTaxiService($taxiServiceId);

            return response()->json([
                'success' => true,
                'data' => $vehicleTypes,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get available vehicle types: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to get available vehicle types: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper method to get the nearest location ID.
     * In a real application, this would use geospatial queries.
     */
    protected function getNearestLocationId(float $latitude, float $longitude): int
    {
        // This is a simplified implementation
        // In a real application, you would use a geospatial query to find the nearest location
        // For now, we'll just return a default location ID or the first active location

        // Example implementation:
        // return DB::table('locations')
        //     ->select('LocationID')
        //     ->selectRaw('ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) as distance', [$longitude, $latitude])
        //     ->orderBy('distance')
        //     ->first()
        //     ->LocationID;

        // For this example, we'll just return 1
        return 1;
    }
}
