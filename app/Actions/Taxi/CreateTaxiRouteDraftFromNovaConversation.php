<?php

namespace App\Actions\Taxi;

use App\Models\NovaTaxiRouteDraft;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class CreateTaxiRouteDraftFromNovaConversation
{
    /**
     * @param  array<string, mixed>  $conversation
     */
    public function handle(array $conversation): NovaTaxiRouteDraft
    {
        $pickupDate = (string) data_get($conversation, 'date.value', now('Europe/Madrid')->toDateString());
        $pickupTime = (string) data_get($conversation, 'time.value', '09:00');
        $token = $this->token($conversation, $pickupDate, $pickupTime);

        return NovaTaxiRouteDraft::query()->updateOrCreate(
            ['token' => $token],
            [
                'tourist_phone' => $conversation['tourist_phone'] ?? null,
                'customer_name' => $conversation['customer_name'] ?? null,
                'customer_phone' => $conversation['customer_phone'] ?? $conversation['tourist_phone'] ?? null,
                'origin' => (string) ($conversation['origin'] ?? 'Origen indicado'),
                'destination' => (string) ($conversation['destination'] ?? 'Destino indicado'),
                'pickup_date' => $pickupDate,
                'pickup_time' => $pickupTime,
                'passengers' => (int) ($conversation['party_size'] ?? 1),
                'status' => 'pending_payment',
                'chbs_url' => $this->chbsUrl($conversation, $pickupDate, $pickupTime, $token),
                'conversation' => $conversation,
                'expires_at' => now()->addDays(2),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $conversation
     */
    private function token(array $conversation, string $pickupDate, string $pickupTime): string
    {
        return sha1(implode('|', [
            (string) ($conversation['tourist_phone'] ?? ''),
            Str::lower((string) ($conversation['origin'] ?? '')),
            Str::lower((string) ($conversation['destination'] ?? '')),
            $pickupDate,
            $pickupTime,
            (string) ($conversation['party_size'] ?? ''),
        ]));
    }

    /**
     * @param  array<string, mixed>  $conversation
     */
    private function chbsUrl(array $conversation, string $pickupDate, string $pickupTime, string $token): string
    {
        $baseUrl = rtrim((string) config('services.taxilanz_woocommerce.route_booking_url', 'https://taxilanz.com/rutas/ruta-redsys/'), '/');
        $date = CarbonImmutable::parse($pickupDate, 'Europe/Madrid')->format('d-m-Y');

        return $baseUrl.'?'.http_build_query([
            'chbs_google_maps_enable' => 1,
            'nova_route_token' => $token,
            'nova_pickup_location' => (string) ($conversation['origin'] ?? ''),
            'nova_dropoff_location' => (string) ($conversation['destination'] ?? ''),
            'nova_pickup_date' => $date,
            'nova_pickup_time' => $pickupTime,
            'nova_passengers' => (int) ($conversation['party_size'] ?? 1),
            'nova_customer_name' => (string) ($conversation['customer_name'] ?? ''),
            'nova_customer_phone' => (string) ($conversation['customer_phone'] ?? $conversation['tourist_phone'] ?? ''),
        ]);
    }
}
