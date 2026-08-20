<?php

namespace App\Services\TaxiBooking;

use App\Events\TaxiBookingCreated;
use App\Events\TaxiBookingDriverAssigned;
use App\Exceptions\NoDriversAvailableException;
use App\Exceptions\TaxiBookingException;
use App\Models\TaxiBooking;
use App\Repositories\Impl\TaxiBookingRepository;
use App\Services\Driver\DriverService;
use App\Services\Vehicle\VehicleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaxiBookingService
{
    public function __construct(
        protected DriverService $driverService,
        protected VehicleService $vehicleService,
        protected TaxiBookingRepository $taxiBookingRepository,

    ) {}

    // Basic CRUD Operations
    public function getAllTaxiBookings(): Collection
    {
        return $this->taxiBookingRepository->all();
    }

    public function getTaxiBookingById(int $id): TaxiBooking
    {
        return $this->taxiBookingRepository->findOrFail($id);
    }

    public function getTaxiBookingsByUserId(int $userId): Collection
    {
        return $this->taxiBookingRepository->findByUser($userId);
    }

    public function createTaxiBooking(array $data): TaxiBooking
    {
        return DB::transaction(function () use ($data) {
            $this->validateBookingData($data);
            $booking = $this->taxiBookingRepository->create($data);

            event(new TaxiBookingCreated($booking));

            return $booking;
        });
    }

    public function updateTaxiBooking(int $id, array $data): TaxiBooking
    {
        return DB::transaction(function () use ($id, $data) {
            $this->validateUpdateData($id, $data);

            return $this->taxiBookingRepository->update($id, $data);
        });
    }

    public function cancelTaxiBooking(int $id): TaxiBooking
    {
        return DB::transaction(function () use ($id) {
            $booking = $this->taxiBookingRepository->findOrFail($id);

            $this->validateCancellation($booking);
            $updated = $this->taxiBookingRepository->update($id, ['status' => 'cancelled']);

            $this->handleCancellationEffects($booking);

            return $updated;
        });
    }

    // Assignment Operations
    public function assignDriver(int $bookingId, int $driverId, ?int $vehicleId = null): TaxiBooking
    {
        return DB::transaction(function () use ($bookingId, $driverId, $vehicleId) {
            $booking = $this->taxiBookingRepository->findOrFail($bookingId);

            $this->validateDriverAssignment($booking, $driverId, $vehicleId);
            $updateData = $this->prepareDriverAssignmentData($driverId, $vehicleId);

            $updated = $this->taxiBookingRepository->update($bookingId, $updateData);
            $this->driverService->markBusy($driverId);

            event(new TaxiBookingDriverAssigned($updated, $booking->driver));

            return $updated;
        });
    }

    // Shared Rides Functionality
    public function findAvailableSharedRides(
        int $pickupLocationId,
        int $dropoffLocationId,
        string $pickupDateTime,
        int $passengerCount
    ): Collection {
        $this->validateSharedRideRequest($passengerCount);

        return $this->taxiBookingRepository->findAvailableSharedRides(
            $pickupLocationId,
            $dropoffLocationId,
            Carbon::parse($pickupDateTime),
            $passengerCount
        );
    }

    // Core Business Logic
    private function validateBookingData(array $data): void
    {
        if (empty($data['pickup_location_id'])) {
            throw new TaxiBookingException('Pickup location is required');
        }

        if ($data['is_shared'] && empty($data['max_additional_passengers'])) {
            throw new TaxiBookingException('Max passengers required for shared rides');
        }
    }

    private function validateDriverAssignment(
        TaxiBooking $booking,
        int $driverId,
        ?int $vehicleId
    ): void {
        // if (!$this->driverService->checkDriverAvailable($driverId)) {
        //     throw new TaxiBookingException('Driver is not available');
        // }

        // if ($vehicleId && !$this->vehicleService->isVehicleAvailable($vehicleId)) {
        //     throw new TaxiBookingException('Vehicle is not available');
        // }

        // if ($vehicleId && !$this->vehicleService->isVehicleAssignedToDriver($vehicleId, $driverId)) {
        //     throw new TaxiBookingException('Vehicle does not belong to driver');
        // }
    }

    private function prepareDriverAssignmentData(
        int $driverId,
        ?int $vehicleId
    ): array {
        $data = ['driver_id' => $driverId, 'status' => 'assigned'];

        if ($vehicleId) {
            $data['vehicle_id'] = $vehicleId;
            $data['status'] = 'vehicle_assigned';
        }

        return $data;
    }

    private function handleCancellationEffects(TaxiBooking $booking): void
    {
        if ($booking->driver_id) {
            $this->driverService->markAvailable($booking->driver_id);
        }

        // if ($booking->vehicle_id) {
        //     $this->vehicleService->markAvailable($booking->vehicle_id);
        // }
    }

    // Advanced Booking Features
    public function bookTaxi(
        int $taxiServiceId,
        string $pickupTime,
        float $pickupLat,
        float $pickupLng,
        int $radius,
        array $bookingDetails
    ): TaxiBooking {
        $availableDrivers = $this->driverService->getAvailableDriversForBooking(
            $taxiServiceId,
            $pickupTime,
            $pickupLat,
            $pickupLng,
            $radius
        );

        if ($availableDrivers->isEmpty()) {
            throw new NoDriversAvailableException;
        }

        return DB::transaction(function () use ($availableDrivers, $bookingDetails) {
            $nearestDriver = $availableDrivers->first();

            $booking = $this->taxiBookingRepository->create(array_merge($bookingDetails, [
                'driver_id' => $nearestDriver->id,
                'vehicle_id' => $nearestDriver->activeVehicle->id,
                'status' => 'confirmed',
            ]));

            $this->driverService->markBusy($nearestDriver->id);
            // $this->vehicleService->markInUse($nearestDriver->activeVehicle->id);

            return $booking;
        });
    }

    // Additional Service Methods
    public function completeBooking(int $bookingId): TaxiBooking
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = $this->taxiBookingRepository->update($bookingId, [
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->driverService->markAvailable($booking->driver_id);
            // $this->vehicleService->markAvailable($booking->vehicle_id);

            return $booking;
        });
    }

    public function getUpcomingBookings(): Collection
    {
        return $this->taxiBookingRepository->upcoming();
    }

    public function getScheduledBookings(): Collection
    {
        return $this->taxiBookingRepository->scheduled();
    }

    private function validateSharedRideRequest(int $passengerCount): void
    {
        if ($passengerCount < 2) {
            throw new TaxiBookingException(
                'Shared rides require at least 2 passengers'
            );
        }

        if ($passengerCount > config('taxi.max_shared_passengers')) {
            throw new TaxiBookingException(
                'Shared rides cannot exceed '.
                config('taxi.max_shared_passengers').' passengers'
            );
        }
    }

    private function validateCancellation(TaxiBooking $booking): void
    {
        if ($booking->status === 'completed') {
            throw new TaxiBookingException(
                'Completed bookings cannot be cancelled'
            );
        }

        if ($booking->pickup_date_time->diffInHours(now()) < 2) {
            throw new TaxiBookingException(
                'Cancellations must be made at least 2 hours before pickup'
            );
        }

        if ($booking->driver && $booking->driver->is_on_trip) {
            throw new TaxiBookingException(
                'Cannot cancel booking with driver currently on trip'
            );
        }
    }

    private function validateUpdateData(int $bookingId, array $data): void
    {
        $booking = $this->taxiBookingRepository->findOrFail($bookingId);

        // Prevent modification of critical fields after confirmation
        if ($booking->status !== 'pending') {
            $protectedFields = [
                'taxi_service_id',
                'vehicle_type_id',
                'pickup_location_id',
                'dropoff_location_id',
            ];

            foreach ($protectedFields as $field) {
                if (array_key_exists($field, $data)) {
                    throw new TaxiBookingException(
                        "Cannot modify $field after booking confirmation"
                    );
                }
            }
        }
    }
}
