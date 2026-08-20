<?php

namespace App\Services\interfaces;

use App\Exceptions\RatingAlreadyExistsException;
use App\Models\Driver;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

interface RatingServiceInterface
{
    /**
     * Create a new rating for a driver with full validation
     *
     * @throws RatingAlreadyExistsException
     */
    public function createDriverRating(
        int $userId,
        int $driverId,
        int $bookingId,
        float $value,
        ?string $comment = null
    ): Rating;

    /**
     * Update rating with flexible parameters
     */
    public function updateRating(
        Rating|int $rating,
        float $value,
        ?string $comment = null
    ): Rating;

    /**
     * Delete rating by instance or ID
     */
    public function deleteRating(int $ratingId): bool;

    /**
     * Get paginated driver ratings with filters
     */
    public function getDriverRatings(
        Driver $driver,
        int $perPage = 15,
        bool $includeHidden = false
    ): LengthAwarePaginator;

    /**
     * Get cached average rating with database fallback
     */
    public function getDriverAverage(Driver $driver): float;

    /**
     * Toggle rating visibility
     */
    public function toggleVisibility(Rating $rating): Rating;

    /**
     * Add admin response to rating
     */
    public function addAdminResponse(Rating $rating, string $response): Rating;

    /**
     * Get recent ratings with optional filters
     */
    public function getRecentRatings(
        ?int $limit = 10,
        ?Driver $driver = null,
        ?User $user = null
    ): Builder;

    /**
     * Get rating by ID
     */
    public function findById(int $ratingId): Builder;
}
