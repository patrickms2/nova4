<?php

namespace App\Actions\Workflow;

use App\Models\Tour;

class MapServiceIdAction
{
    /**
     * Resolve the local Tour ID for a selected service.
     *
     * The MCP get-services response returns WordPress/LatePoint service IDs,
     * which differ from the local Tour IDs that the /explore booking flow uses.
     * We translate the external ID to the local Tour ID via externalSyncMappings.
     *
     * Expected payload keys:
     *   - service_id : int|string (external WP/LatePoint service ID selected by user)
     *   - service_map : array<string, int|string> (nombre -> external WP id, optional)
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function __invoke(array $payload): array
    {
        $serviceId = $payload['service_id'] ?? null;

        if (! $serviceId) {
            return ['error' => 'service_id is required.'];
        }

        $externalId = is_numeric($serviceId) ? (int) $serviceId : null;
        $tour = $this->resolveTour(null, $externalId);

        return [
            'service_id' => $tour?->id,
            'external_id' => $externalId,
        ];
    }

    private function resolveTour(string $visitTypeName, int|string|null $externalId): ?Tour
    {
        // 1. Match by external sync mapping ID (most reliable).
        if (! blank($externalId)) {
            $tour = Tour::query()
                ->where('is_active', true)
                ->whereHas('externalSyncMappings', fn ($query) => $query->where('external_id', (string) $externalId))
                ->first();

            if ($tour) {
                return $tour;
            }
        }

        // 2. Fallback: match local tour by name (handles label suffixes like "ESP").
        return Tour::query()
            ->where('is_active', true)
            ->where(function ($query) use ($visitTypeName) {
                $query->where('tour_name', $visitTypeName)
                    ->orWhere('tour_name', 'like', '%'.$visitTypeName.'%')
                    ->orWhereRaw('? like concat(\'%\', tour_name, \'%\')', [$visitTypeName]);
            })
            ->first();
    }
}
