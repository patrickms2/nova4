<?php

namespace App\Repositories\Impl;

use App\Events\TripCreated;
use App\Jobs\ProcessTripPayment;
use App\Models\Trip;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use TarfinLabs\LaravelSpatial\Types\Point;

class TripRepository
{
    /**
     * Get all trips
     */
    public function all(): Collection
    {
        return Trip::with(['driver', 'user'])->get();
    }

    /**
     * Get a trip by ID or fail
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Trip
    {
        return Trip::with(['driver.user', 'vehicle', 'taxiService'])
            ->findOrFail($id);
    }

    /**
     * Get trips by user
     */
    public function getByUser(int $userId): Collection
    {
        return Trip::where('user_id', $userId)
            ->with(['driver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get trips by driver
     */
    public function getByDriver(int $driverId): Collection
    {
        return Trip::where('driver_id', $driverId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get trips by status
     */
    public function getByStatus(string $status): Collection
    {
        return Trip::where('status', $status)
            ->with(['driver', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getNearbyTrips(float $lat, float $lng, int $radius = 5): Collection
    {
        return Trip::whereRaw(
            'ST_Distance_Sphere(pickup_location, POINT(?, ?)) <= ?',
            [$lng, $lat, $radius * 1000]
        )->where('status', 'pending')
            ->get();
    }

    /**
     * Create a new trip
     */
    public function create(array $data): Trip
    {
        return DB::transaction(function () use ($data) {
            $trip = Trip::create($this->parseLocationData($data));

            event(new TripCreated($trip));
            dispatch(new ProcessTripPayment($trip));

            return $trip;
        });
    }

    private function parseLocationData(array $data): array
    {
        $pointKeys = ['pickup', 'dropoff'];

        foreach ($pointKeys as $key) {
            if (isset($data["{$key}_lat"], $data["{$key}_lng"])) {
                $data["{$key}_location"] = new Point(
                    $this->validateCoordinate($data["{$key}_lat"]),
                    $this->validateCoordinate($data["{$key}_lng"])
                );
                unset($data["{$key}_lat"], $data["{$key}_lng"]);
            }
        }

        return $data;
    }

    private function validateCoordinate(float $value): float
    {
        if ($value < -90 || $value > 90) {
            throw new Exception;
        }

        return $value;
    }

    /**
     * Update a trip
     */
    public function update(int $id, array $data): bool
    {
        // Set pickup and dropoff locations if provided
        if (isset($data['pickup_lat']) && isset($data['pickup_lng'])) {
            $data['pickup_location'] = DB::raw("POINT({$data['pickup_lng']}, {$data['pickup_lat']})");
            unset($data['pickup_lat'], $data['pickup_lng']);
        }

        if (isset($data['dropoff_lat']) && isset($data['dropoff_lng'])) {
            $data['dropoff_location'] = DB::raw("POINT({$data['dropoff_lng']}, {$data['dropoff_lat']})");
            unset($data['dropoff_lat'], $data['dropoff_lng']);
        }

        return Trip::where('id', $id)->update($data);
    }

    /**
     * Update trip status
     */
    public function updateStatus(int $id, string $status): bool
    {
        $data = ['status' => $status];

        // Add timestamps based on status
        if ($status === 'in_progress') {
            $data['started_at'] = now();
        } elseif ($status === 'completed') {
            $data['completed_at'] = now();
        }

        return $this->update($id, $data);
    }

    /**
     * Delete a trip
     */
    public function delete(int $id): bool
    {
        return Trip::where('id', $id)->delete();
    }

    /**
     * Find a pending trip with a database lock
     */
    public function findPendingWithLock(int $tripId): ?Trip
    {
        return Trip::where('id', $tripId)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->first();
    }

    public function calculateFare(Trip $trip): void
    {
        $baseRate = $trip->taxiService->base_price;
        $distanceRate = $trip->taxiService->price_per_km;

        $trip->update([
            'fare' => ($baseRate + ($trip->distance_km * $distanceRate)) * $trip->surge_multiplier,
        ]);
    }
}
