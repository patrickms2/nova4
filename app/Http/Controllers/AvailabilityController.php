<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use App\Models\AvailabilitySlot;
use App\Models\Rental;

/**
 * GET /api/rentals/availability/{rentable}
 *
 * Zwraca zajete daty zasobu w zakresie [from, to]. Frontend uzywa do
 * wylaczenia dni w date pickerze.
 *
 * Identyfikator rentable: slug lub UUID/id (autoresolve).
 *
 * Query params:
 *  - from (YYYY-MM-DD, default = today)
 *  - to   (YYYY-MM-DD, default = today + 90 dni)
 *
 * Response 200:
 *   { "rentable": {"id":..., "slug":..., "name":...},
 *     "from": "...", "to": "...",
 *     "occupied": [
 *       {"start":"2026-05-01","end":"2026-05-03","type":"rental","status":"paid"},
 *       {"start":"2026-05-10","end":"2026-05-12","type":"block","reason":"Serwis"}
 *     ]
 *   }
 *
 * @see KML-0056 (E1)
 */
class AvailabilityController extends Controller
{
    public function show(Request $request, string $rentable): JsonResponse
    {
        $rentableModel = $this->resolveRentable($rentable);

        if ($rentableModel === null) {
            return response()->json(['message' => 'Zasob nie znaleziony.'], 404);
        }

        $from = $request->query('from') ?: Carbon::today()->toDateString();
        $to = $request->query('to') ?: Carbon::today()->addDays(90)->toDateString();

        $morphClass = $rentableModel->getMorphClass();
        $rentableId = $rentableModel->getKey();

        $rentals = Rental::query()
            ->where('rentable_type', $morphClass)
            ->where('rentable_id', $rentableId)
            ->active()
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from)
            ->orderBy('start_date')
            ->get(['start_date', 'end_date', 'status']);

        $slots = AvailabilitySlot::query()
            ->where('rentable_type', $morphClass)
            ->where('rentable_id', $rentableId)
            ->blocked()
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from)
            ->orderBy('start_date')
            ->get(['start_date', 'end_date', 'reason']);

        $occupied = collect();

        foreach ($rentals as $r) {
            $occupied->push([
                'start' => Carbon::parse($r->start_date)->toDateString(),
                'end' => Carbon::parse($r->end_date)->toDateString(),
                'type' => 'rental',
                'status' => $r->status,
            ]);
        }

        foreach ($slots as $s) {
            $occupied->push([
                'start' => Carbon::parse($s->start_date)->toDateString(),
                'end' => Carbon::parse($s->end_date)->toDateString(),
                'type' => 'block',
                'reason' => $s->reason,
            ]);
        }

        return response()->json([
            'rentable' => [
                'id' => $rentableId,
                'slug' => $rentableModel->slug ?? null,
                'name' => $rentableModel->name ?? $rentableModel->title ?? null,
            ],
            'from' => $from,
            'to' => $to,
            'occupied' => $occupied->sortBy('start')->values(),
        ]);
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

        // Najpierw slug (typowe dla SEO-friendly URL frontendu)
        $bySlug = (clone $query)->where('slug', $identifier)->first();
        if ($bySlug !== null) {
            return $bySlug;
        }

        // Potem klucz glowny
        return (clone $query)->whereKey($identifier)->first();
    }
}
