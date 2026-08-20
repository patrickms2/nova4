<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaExternalBooking;
use App\Models\NovaExternalCatalogItem;
use App\Models\NovaIntegrationSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class NovaLatePointApiSyncService
{
    public function __construct(
        private readonly NovaExternalSyncSupport $support,
    ) {}

    public function sync(NovaIntegrationSetting $setting, bool $fullSync = false): array
    {
        $summary = [
            'services_processed' => 0,
            'bookings_processed' => 0,
            'created' => 0,
            'updated' => 0,
        ];

        $this->support->markSyncStarted($setting);

        try {
            foreach ($this->getServices($setting) as $service) {
                $payload = $this->normalizeService($setting, $service);
                $item = NovaExternalCatalogItem::query()->updateOrCreate(
                    [
                        'source' => 'latepoint',
                        'external_id' => $payload['external_id'],
                        'external_item_id' => null,
                    ],
                    $payload,
                );

                $summary[$item->wasRecentlyCreated ? 'created' : 'updated']++;
                $summary['services_processed']++;
            }

            foreach ($this->getBookings($setting) as $booking) {
                $payload = $this->normalizeBooking($setting, $booking);
                $externalBooking = NovaExternalBooking::query()->updateOrCreate(
                    [
                        'source' => 'latepoint',
                        'external_id' => $payload['external_id'],
                    ],
                    $payload,
                );

                $summary[$externalBooking->wasRecentlyCreated ? 'created' : 'updated']++;
                $summary['bookings_processed']++;
            }

            $this->support->markSyncFinished($setting);
            $this->support->logSync($setting, 'nova:sync-latepoint-api', 'mixed', [
                'processed' => $summary['services_processed'] + $summary['bookings_processed'],
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'detail' => $summary,
            ]);

            return $summary;
        } catch (\Throwable $exception) {
            $this->support->markSyncFailed($setting, $exception);
            $this->support->logSync($setting, 'nova:sync-latepoint-api', 'mixed', [
                'processed' => $summary['services_processed'] + $summary['bookings_processed'],
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'detail' => $summary,
            ], 'failed', $exception);

            throw $exception;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchServices(NovaIntegrationSetting $setting, array $query = []): array
    {
        return $this->getServices($setting, $query);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchBookings(NovaIntegrationSetting $setting, array $query = []): array
    {
        return $this->getBookings($setting, $query);
    }

    private function http(NovaIntegrationSetting $setting): PendingRequest
    {
        $credentials = $setting->credentials ?? [];
        $username = (string) data_get($credentials, 'username', data_get($credentials, 'user', ''));
        $password = (string) data_get($credentials, 'application_password', data_get($credentials, 'password', ''));
        $request = Http::baseUrl(rtrim((string) $setting->base_url, '/').'/wp-json/wp-abilities/v1/abilities')
            ->acceptJson()
            ->timeout((int) data_get($setting->settings, 'timeout', 30));

        $host = parse_url((string) $setting->base_url, PHP_URL_HOST);

        if (is_string($host) && str_ends_with($host, '.test')) {
            $request = $request->withoutVerifying();
        }

        if (data_get($setting->settings, 'verify_ssl') === false) {
            $request = $request->withoutVerifying();
        }

        if ($username !== '' && $password !== '') {
            return $request->withBasicAuth($username, $password);
        }

        return $request;
    }

    private function getServices(NovaIntegrationSetting $setting, array $query = []): array
    {
        $response = $this->http($setting)->post('latepoint/get-services/run', [
            'input' => array_merge([
                'per_page' => (int) data_get($setting->settings, 'page_size', 100),
            ], $query),
        ]);

        $response->throw();

        return $response->json('services') ?? [];
    }

    private function getBookings(NovaIntegrationSetting $setting, array $query = []): array
    {
        $response = $this->http($setting)->post('latepoint/list-bookings/run', [
            'input' => array_merge([
                'per_page' => (int) data_get($setting->settings, 'page_size', 100),
            ], $query),
        ]);

        $response->throw();

        return $response->json('bookings') ?? [];
    }

    private function normalizeService(NovaIntegrationSetting $setting, array $service): array
    {
        $externalId = (string) ($service['id'] ?? '');

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'nova_integration_setting_id' => $setting->id,
            'source' => 'latepoint',
            'external_id' => $externalId,
            'external_item_id' => null,
            'type' => 'service',
            'status' => $service['status'] ?? null,
            'name' => $service['name'] ?? 'Servicio',
            'description' => $service['description'] ?? null,
            'price' => $service['price'] ?? null,
            'currency' => 'EUR',
            'duration_minutes' => isset($service['duration']) ? (int) $service['duration'] : null,
            'metadata' => ['raw' => $service],
            'source_updated_at' => isset($service['updated_at']) ? CarbonImmutable::parse($service['updated_at']) : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'latepoint', 'id' => $externalId])),
            'last_synced_at' => now(),
        ];
    }

    private function normalizeBooking(NovaIntegrationSetting $setting, array $booking): array
    {
        $externalId = (string) ($booking['id'] ?? '');
        $bookingTime = $this->normalizeMinutesToTime($booking['start_time'] ?? null);
        $bookingDate = $booking['start_date'] ?? null;
        $startsAt = filled($bookingDate) ? CarbonImmutable::parse((string) $bookingDate.' '.$bookingTime) : null;

        return [
            'nova_business_id' => $setting->nova_business_id,
            'nova_service_id' => $setting->nova_service_id,
            'source' => 'latepoint',
            'external_id' => $externalId,
            'external_item_id' => isset($booking['service_id']) ? (string) $booking['service_id'] : null,
            'intent_key' => null,
            'service_name' => $booking['service_name'] ?? null,
            'booking_date' => $startsAt?->toDateString(),
            'booking_time' => $startsAt?->format('H:i:s'),
            'booking_starts_at' => $startsAt,
            'attendees' => (int) ($booking['total_attendees'] ?? $booking['attendees'] ?? 1),
            'customer_name' => $booking['customer_name'] ?? null,
            'customer_email' => $booking['customer_email'] ?? $booking['email'] ?? null,
            'customer_phone' => $booking['customer_phone'] ?? $booking['phone'] ?? null,
            'currency' => 'EUR',
            'booking_status' => $this->mapBookingStatus($booking['status'] ?? null),
            'payment_status' => $this->mapPaymentStatus($booking['payment_status'] ?? null),
            'metadata' => ['raw' => $booking],
            'source_updated_at' => isset($booking['updated_at']) ? CarbonImmutable::parse($booking['updated_at']) : null,
            'source_fingerprint' => sha1(json_encode(['source' => 'latepoint', 'booking_id' => $externalId])),
            'last_synced_at' => now(),
        ];
    }

    private function normalizeMinutesToTime(mixed $value): string
    {
        if (blank($value)) {
            return '09:00:00';
        }

        if (is_numeric($value)) {
            $minutes = max(0, min((int) $value, 24 * 60 - 1));

            return CarbonImmutable::createFromTime(0, 0)->addMinutes($minutes)->format('H:i:s');
        }

        $string = trim((string) $value);

        if (preg_match('/^\d{1,2}:\d{2}/', $string) === 1) {
            return strlen($string) === 5 ? "{$string}:00" : $string;
        }

        return '09:00:00';
    }

    private function mapBookingStatus(?string $status): string
    {
        return match ($status) {
            'approved', 'confirmed' => 'approved',
            'completed' => 'completed',
            'cancelled', 'canceled' => 'cancelled',
            'incident' => 'incident',
            default => 'pending',
        };
    }

    private function mapPaymentStatus(?string $status): string
    {
        return match ($status) {
            'fully_paid', 'paid', 'completed' => 'paid',
            'partial', 'partially_paid' => 'partial',
            'refunded' => 'refunded',
            'mismatch', 'failed' => 'mismatch',
            default => 'unpaid',
        };
    }
}
