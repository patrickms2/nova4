<?php

namespace App\Actions\Workflow;

use App\Models\NovaBusiness;
use App\Models\Tour;

class ListTourServicesAction
{
    /**
     * List active tour services for a business.
     *
     * Expected payload keys:
     *   - business_slug : string (e.g. "la-geria")
     *   - business_id   : int (optional)
     */
    public function __invoke(array $payload): array
    {
        $businessSlug = $payload['business_slug'] ?? null;
        $businessId = $payload['business_id'] ?? null;

        $business = null;
        if ($businessId) {
            $business = NovaBusiness::find($businessId);
        } elseif ($businessSlug) {
            $business = NovaBusiness::where('slug', $businessSlug)->first();
        }

        $query = Tour::query()->where('is_active', true);
        if ($business) {
            // Tours may be linked via server_id if the business has a configured server
            $serverIds = $business->mcpServers()->pluck('servers.id')->all();
            if ($serverIds !== []) {
                $query->whereIn('server_id', $serverIds);
            }
        }

        $tours = $query->orderBy('tour_name')->get()->map(static fn (Tour $tour): array => [
            'id' => $tour->id,
            'service_id' => $tour->id,
            'name' => $tour->tour_name,
            'description' => $tour->description ?? '',
            'price' => $tour->base_price,
            'duration' => $tour->duration_days ? $tour->duration_days.' day(s)' : ($tour->duration_hours ? $tour->duration_hours.' hour(s)' : ''),
            'status' => 'active',
        ])->values()->all();

        $choices = collect($tours)->pluck('name')->all();
        $serviceMap = collect($tours)->pluck('id', 'name')->all();

        return [
            'success' => true,
            'source' => $business?->name ?? 'Local tours',
            'type' => 'services',
            'data' => $tours,
            'choices' => implode(',', $choices),
            'service_map' => $serviceMap,
            'services' => $tours,
        ];
    }
}
