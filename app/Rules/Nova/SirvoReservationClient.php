<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaMcpServer;
use Illuminate\Support\Facades\Http;

final class SirvoReservationClient
{
    /**
     * @return array<string, mixed>
     */
    public function checkCapacity(NovaMcpServer $server, string $date, string $time, int $guests): array
    {
        $restaurantId = (string) config('services.sirvo.default_restaurant_id');

        if ($restaurantId === '') {
            return [
                'checked' => false,
                'available' => null,
                'reason' => 'SIRVO_DEFAULT_RESTAURANT_ID is not configured',
            ];
        }

        $response = Http::timeout(10)
            ->acceptJson()
            ->asJson()
            ->post(rtrim($server->endpoint_url, '/').'/api/capacity', [
                'restaurantId' => $restaurantId,
                'date' => $date,
                'time' => $time,
                'guests' => $guests,
                'timezone' => 'Europe/Madrid',
            ]);

        if (! $response->successful()) {
            return [
                'checked' => false,
                'available' => null,
                'status' => $response->status(),
                'reason' => (string) $response->body(),
            ];
        }

        return [
            'checked' => true,
            'available' => true,
            'status' => $response->status(),
            'restaurant_id' => $restaurantId,
            'date' => $date,
            'time' => $time,
            'guests' => $guests,
            'capacity' => $response->json(),
        ];
    }
}
