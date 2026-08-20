<?php

namespace App\Actions\Workflow;

use Illuminate\Support\Facades\Http;

class CheckAvailabilityAction
{
    /**
     * Check tour availability for La Geria.
     *
     * Expected payload keys:
     *   - service_id  : The tour service ID
     *   - date        : The date in YYYY-MM-DD format
     *   - participants: Number of participants (default: 2)
     */
    public function __invoke(array $payload): array
    {
        $serviceId = $payload['service_id'] ?? null;
        $date = $payload['date'] ?? null;
        $participants = $payload['participants'] ?? 2;

        if (! $serviceId || ! $date) {
            return ['error' => 'service_id and date are required.'];
        }

        try {
            $url = config('app.url').'/explore/availability';

            $response = Http::timeout(15)->get($url, [
                'type' => 'tour_visit',
                'service_id' => $serviceId,
                'date' => $date,
                'participants' => $participants,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return ['result' => $data];
            }

            return ['error' => 'Failed to check availability', 'status' => $response->status()];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
