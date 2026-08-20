<?php

namespace App\Actions\Taxi;

use App\Models\TaxiBooking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CreateTaxilanzWooOrderForTaxiBooking
{
    public function handle(TaxiBooking $taxiBooking): ?int
    {
        if (! config('services.taxilanz_woocommerce.enabled')) {
            return null;
        }

        $booking = $taxiBooking->booking;

        if ($booking === null) {
            return null;
        }

        $existingWooOrderId = $this->existingWooOrderId((string) $booking->special_requests);

        if ($existingWooOrderId !== null) {
            return $existingWooOrderId;
        }

        $endpoint = rtrim((string) config('services.taxilanz_woocommerce.endpoint'), '/');
        $consumerKey = (string) config('services.taxilanz_woocommerce.consumer_key');
        $consumerSecret = (string) config('services.taxilanz_woocommerce.consumer_secret');

        if ($endpoint === '' || $consumerKey === '' || $consumerSecret === '') {
            return null;
        }

        $response = Http::withBasicAuth($consumerKey, $consumerSecret)
            ->acceptJson()
            ->timeout(20)
            ->post($endpoint.'/wp-json/wc/v3/orders', $this->payload($taxiBooking));

        $response->throw();

        $wooOrderId = (int) data_get($response->json(), 'id');

        if ($wooOrderId > 0) {
            $booking->forceFill([
                'special_requests' => trim(((string) $booking->special_requests).PHP_EOL.'Woo order: '.$wooOrderId),
                'last_updated' => now(),
            ])->save();
        }

        return $wooOrderId > 0 ? $wooOrderId : null;
    }

    private function existingWooOrderId(string $specialRequests): ?int
    {
        if (preg_match('/Woo order:\s*(\d+)/i', $specialRequests, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(TaxiBooking $taxiBooking): array
    {
        $booking = $taxiBooking->booking;
        $customerName = $this->customerName((string) $booking?->special_requests);
        $parts = preg_split('/\s+/', trim($customerName), 2) ?: [];
        $firstName = $parts[0] ?? 'Cliente';
        $lastName = $parts[1] ?? 'Nova';
        $phone = $this->customerPhone((string) $booking?->special_requests);
        $reference = (string) $booking?->booking_reference;
        $origin = (string) ($taxiBooking->pickupLocation?->name ?? 'Origen indicado');
        $destination = (string) ($taxiBooking->dropoffLocation?->name ?? 'Destino indicado');

        return [
            'status' => 'on-hold',
            'payment_method' => 'cod',
            'payment_method_title' => 'Pago al conductor',
            'set_paid' => false,
            'customer_note' => sprintf(
                'Reserva taxi Nova %s: %s -> %s. Fecha/hora: %s. Pasajeros: %s.',
                $reference,
                $origin,
                $destination,
                $taxiBooking->pickup_date_time?->timezone('Europe/Madrid')->format('Y-m-d H:i') ?? 'No indicada',
                (string) $taxiBooking->passenger_count,
            ),
            'billing' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ],
            'fee_lines' => [
                [
                    'name' => 'Reserva taxi '.$origin.' → '.$destination,
                    'total' => '0.00',
                ],
            ],
            'meta_data' => [
                ['key' => '_nova_booking_reference', 'value' => $reference],
                ['key' => '_nova_booking_id', 'value' => (string) $booking?->id],
                ['key' => '_nova_taxi_booking_id', 'value' => (string) $taxiBooking->id],
                ['key' => '_nova_pickup_location', 'value' => $origin],
                ['key' => '_nova_dropoff_location', 'value' => $destination],
                ['key' => '_nova_pickup_datetime', 'value' => $taxiBooking->pickup_date_time?->timezone('Europe/Madrid')->format('Y-m-d H:i:s')],
                ['key' => '_nova_passengers', 'value' => (string) $taxiBooking->passenger_count],
                ['key' => '_created_by', 'value' => 'nova_chat_gateway'],
            ],
        ];
    }

    private function customerName(string $specialRequests): string
    {
        if (preg_match('/Customer:\s*(.+?)\./i', $specialRequests, $matches) === 1) {
            return trim($matches[1]);
        }

        return 'Cliente Nova';
    }

    private function customerPhone(string $specialRequests): string
    {
        if (preg_match('/Phone:\s*(.+?)\./i', $specialRequests, $matches) === 1) {
            return Str::of($matches[1])->trim()->toString();
        }

        return '';
    }
}
