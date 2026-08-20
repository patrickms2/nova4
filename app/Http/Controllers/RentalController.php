<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Exceptions\RentalConflictException;
use App\Http\Requests\StoreRentalRequest;
use App\Services\RentalService;

/**
 * POST /api/rentals — utworzenie rezerwacji.
 *
 * Resolwuje rentable po slug lub PK z config('rental.rentable_model'),
 * deleguje booking do RentalService (DB::transaction + lockForUpdate),
 * zwraca 201 z payloadem klienta lub 409 przy konflikcie / 404 gdy zasob
 * nie znaleziony / 422 walidacja FormRequest.
 *
 * @see KML-0057 (E2)
 */
class RentalController extends Controller
{
    public function __construct(protected RentalService $service) {}

    public function store(StoreRentalRequest $request): JsonResponse
    {
        $data = $request->validated();

        $rentableModel = $this->resolveRentable($data['rentable']);

        if ($rentableModel === null) {
            return response()->json([
                'message' => 'Zasob nie znaleziony.',
            ], 404);
        }

        try {
            $rental = $this->service->book(
                rentable: $rentableModel,
                startDate: $data['start_date'],
                endDate: $data['end_date'],
                payload: [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'message' => $data['message'] ?? null,
                    'qty' => $data['qty'] ?? 1,
                    // null (a nie 0) — pozwala PricingStrategy policzyc kwote w RentalService.
                    // Klient API moze podac total_amount jawnie (np. promocja), wtedy strategia jest pomijana.
                    'total_amount' => $data['total_amount'] ?? null,
                    'currency' => $data['currency'] ?? config('rental.currency', 'PLN'),
                    'locale' => $data['locale'] ?? 'pl',
                    'gdpr_consent' => (bool) $data['gdpr_consent'],
                ],
                rentalTypeId: $data['rental_type_id'] ?? null,
            );
        } catch (RentalConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'rental_conflict',
            ], 409);
        }

        return response()->json([
            'id' => $rental->id,
            'status' => $rental->status,
            'total_amount' => $rental->total_amount,
            'currency' => $rental->currency,
            'requires_payment' => ((int) $rental->total_amount) > 0,
            'rentable' => [
                'id' => $rentableModel->getKey(),
                'slug' => $rentableModel->slug ?? null,
                'name' => $rentableModel->name ?? $rentableModel->title ?? null,
            ],
            'start_date' => $rental->start_date instanceof \DateTimeInterface
                ? $rental->start_date->format('Y-m-d')
                : (string) $rental->start_date,
            'end_date' => $rental->end_date instanceof \DateTimeInterface
                ? $rental->end_date->format('Y-m-d')
                : (string) $rental->end_date,
        ], 201);
    }

    /**
     * Znajduje rentable po slug, kluczu glownym lub UUID.
     */
    protected function resolveRentable(string $identifier): ?Model
    {
        $modelClass = config('rental.rentable_model');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return null;
        }

        /** @var Model $model */
        $model = new $modelClass;
        $query = method_exists($model, 'newQueryWithoutScopes')
            ? $model->newQueryWithoutScopes()
            : $model->newQuery();

        $bySlug = (clone $query)->where('slug', $identifier)->first();
        if ($bySlug !== null) {
            return $bySlug;
        }

        return (clone $query)->whereKey($identifier)->first();
    }
}
