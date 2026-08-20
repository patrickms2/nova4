<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Resources\TripResource;
use App\Services\Driver\DriverService;
use App\Services\Trip\TripService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    protected $tripService;

    protected $driverService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TripService $tripService, DriverService $driverService)
    {
        $this->tripService = $tripService;
        $this->driverService = $driverService;
        // $this->middleware('auth');
    }

    /**
     * Display a listing of the user's trips.
     */
    public function index(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $trips = $this->tripService->getTripsByUser($userId);

            return response()->json([
                'success' => true,
                'data' => TripResource::collection($trips),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user trips: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve trips',
            ], 500);
        }
    }

    /**
     * Store a newly created trip request in storage.
     *
     * @param  \App\Http\Requests\Api\Trip\StoreTripRequest  $request
     */
    public function store(StoreTripRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['user_id'] = Auth::id();

            // Create trip request using service method
            $trip = $this->tripService->createTripRequest($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Trip request created successfully',
                'data' => new TripResource($trip),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create trip request: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to create trip request: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified trip.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $trip = $this->tripService->getTripById($id);

            // Check if the trip belongs to the authenticated user
            if ($trip->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new TripResource($trip),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Trip not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trip: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve trip',
            ], 500);
        }
    }

    /**
     * Cancel a trip.
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $trip = $this->tripService->getTripById($id);

            // Check if the trip belongs to the authenticated user
            if ($trip->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            // Cancel the trip using service method
            $cancelledTrip = $this->tripService->cancelTrip($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Trip cancelled successfully',
                'data' => new TripResource($cancelledTrip),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Trip not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel trip: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel trip: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rate a completed trip.
     */
    public function rateTrip(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $trip = $this->tripService->getTripById($id);

            // Check if the trip belongs to the authenticated user
            if ($trip->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            // Check if the trip is completed
            if ($trip->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only completed trips can be rated',
                ], 422);
            }

            // Create rating using service method
            $rating = $this->tripService->createTripRating(
                Auth::id(),
                $trip->driver_id,
                $trip->booking_id, // Add the missing booking_id parameter
                $request->input('rating'),
                $request->input('comment')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rating submitted successfully',
                'data' => $rating,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Trip not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to rate trip: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to rate trip: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate fare for a trip.
     */
    public function calculateFare(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_type_id' => 'required|integer|exists:vehicle_types,id',
            'distance' => 'required|numeric|min:0.1',
        ]);

        try {
            $fare = $this->tripService->calculateFare(
                $request->input('vehicle_type_id'),
                $request->input('distance')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'fare' => $fare,
                    'currency' => 'USD', // Assuming USD as default currency
                    'distance' => $request->input('distance'),
                    'unit' => 'km', // Assuming kilometers as default unit
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Vehicle type not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to calculate fare: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to calculate fare: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start a trip.
     */
    public function startTrip(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $trip = $this->tripService->getTripById($id);

            // Check if the trip belongs to the authenticated user
            if ($trip->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            // Start the trip using service method
            $startedTrip = $this->tripService->startTrip($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Trip started successfully',
                'data' => new TripResource($startedTrip),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Trip not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to start trip: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to start trip: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete a trip.
     */
    public function completeTrip(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'distance' => 'required|numeric|min:0.1',
            'additional_notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $trip = $this->tripService->getTripById($id);

            // Check if the trip belongs to the authenticated user
            if ($trip->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            // Check if the trip is in progress
            if ($trip->status !== 'in_progress') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only in-progress trips can be completed',
                ], 422);
            }

            // Additional data for trip completion
            $additionalData = [];
            if ($request->has('additional_notes')) {
                $additionalData['notes'] = $request->input('additional_notes');
            }

            // Complete the trip using service method
            $completedTrip = $this->tripService->completeTrip(
                $id,
                $request->input('distance'),
                $additionalData
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Trip completed successfully',
                'data' => new TripResource($completedTrip),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Trip not found',
            ], 404);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete trip: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to complete trip: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get trips by status.
     */
    public function getTripsByStatus(string $status): JsonResponse
    {
        try {
            $userId = Auth::id();
            $trips = $this->tripService->getTripsByStatus($status);

            // Filter trips to only show those belonging to the authenticated user
            $userTrips = $trips->filter(function ($trip) use ($userId) {
                return $trip->user_id === $userId;
            });

            return response()->json([
                'success' => true,
                'data' => TripResource::collection($userTrips),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trips by status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve trips by status',
            ], 500);
        }
    }

    /**
     * Assign a driver to a trip.
     */
    public function assignDriver(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'driver_id' => 'required|integer|exists:drivers,id',
        ]);

        try {
            DB::beginTransaction();

            $trip = $this->tripService->getTripById($id);

            // Check if the trip belongs to the authenticated user
            if ($trip->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            // Check if the trip is in pending status
            if ($trip->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only pending trips can be assigned a driver',
                ], 422);
            }

            // Assign driver using service method
            $updatedTrip = $this->tripService->acceptTrip(
                $id,
                $request->input('driver_id')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Driver assigned successfully',
                'data' => new TripResource($updatedTrip),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Trip or driver not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign driver: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to assign driver: '.$e->getMessage(),
            ], 500);
        }
    }
}
