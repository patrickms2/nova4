<?php

namespace App\Services\ExternalSync;

use App\Models\ExternalCatalogItem;
use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use App\Models\PublicBookingRequest;
use App\Models\Tour;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RemoteBookingCreator
{
    public function __construct(
        private readonly ExternalSyncManager $syncManager,
    ) {}

    public function materializeLatepointRequest(PublicBookingRequest $bookingRequest): bool
    {
        if ($bookingRequest->remote_source_platform !== 'latepoint') {
            return false;
        }

        if ($bookingRequest->remote_booking_status !== 'created') {
            return false;
        }

        if (blank($bookingRequest->remote_external_id)) {
            return false;
        }

        if ($bookingRequest->type !== 'tour') {
            return false;
        }

        /** @var Tour|null $tour */
        $tour = Tour::query()->find($bookingRequest->service_id);
        if (! $tour) {
            return false;
        }

        $mapping = $this->mappingFor($bookingRequest, $tour);
        $source = $mapping?->source;

        if (! $mapping || ! $source) {
            return false;
        }

        $response = is_array($bookingRequest->remote_response) ? $bookingRequest->remote_response : [];

        // Best-effort input reconstruction (for projector fields).
        $input = [
            'service_id' => (int) $mapping->external_id,
            'status' => 'approved',
        ];

        $this->stageLatepointBookingLocally($source, $mapping, $bookingRequest, (string) $bookingRequest->remote_external_id, $response, $input);

        return true;
    }

    /**
     * @return array{status: string, source_platform?: string|null, source_label?: string|null, external_id?: string|null, error?: string|null}
     */
    public function create(PublicBookingRequest $bookingRequest, object $service): array
    {
        $mapping = $this->mappingFor($bookingRequest, $service);
        $source = $mapping?->source;

        if (! $mapping || ! $source) {
            return $this->persist($bookingRequest, [
                'status' => 'skipped',
                'source_platform' => null,
                'source_label' => null,
                'external_id' => null,
                'response' => null,
                'error' => 'No external source mapping found for this service.',
            ]);
        }

        try {
            $result = match ($source->source_platform) {
                'sirvo' => $this->createSirvoReservation($source, $mapping, $bookingRequest),
                'latepoint' => $this->createLatePointBooking($source, $mapping, $bookingRequest),
                'woo' => $this->createWooTaxiRouteCheckout($source, $mapping, $bookingRequest),
                default => [
                    'status' => 'skipped',
                    'external_id' => null,
                    'response' => null,
                    'error' => 'Remote creation is not configured for '.$source->source_platform.'.',
                ],
            };

            return $this->persist($bookingRequest, [
                'source_platform' => $source->source_platform,
                'source_label' => $source->source_label,
                ...$result,
            ]);
        } catch (Throwable $exception) {
            return $this->persist($bookingRequest, [
                'status' => 'failed',
                'source_platform' => $source->source_platform,
                'source_label' => $source->source_label,
                'external_id' => null,
                'response' => null,
                'error' => Str::limit($exception->getMessage(), 5000, ''),
            ]);
        }
    }

    private function mappingFor(PublicBookingRequest $bookingRequest, object $service): ?ExternalSyncMapping
    {
        if (! method_exists($service, 'externalSyncMappings')) {
            return null;
        }

        return $service->externalSyncMappings()
            ->with(['source.server'])
            ->whereIn('resource_type', $this->resourceTypesFor($bookingRequest))
            ->latest('last_synced_at')
            ->latest('id')
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function resourceTypesFor(PublicBookingRequest $bookingRequest): array
    {
        return match ($bookingRequest->type) {
            'restaurant' => ['restaurant', 'restaurant_booking'],
            'tour', 'transfer' => ['tour_visit', 'tour_route', 'tour'],
            'taxi' => ['taxi', 'taxi_booking'],
            'hotel' => ['hotel', 'hotel_booking'],
            default => [$bookingRequest->type],
        };
    }

    /**
     * @return array{status: string, external_id: string|null, response: array<string, mixed>|null, error: string|null}
     */
    private function createSirvoReservation(ExternalSource $source, ExternalSyncMapping $mapping, PublicBookingRequest $bookingRequest): array
    {
        if ($bookingRequest->type !== 'restaurant') {
            return [
                'status' => 'skipped',
                'external_id' => null,
                'response' => null,
                'error' => 'Sirvo remote creation only supports restaurant requests.',
            ];
        }

        $path = (string) (data_get($source->settings, 'booking_create.path') ?: 'api/reservations');
        $response = $this->request($source)
            ->post($path, [
                'restaurantId' => $mapping->external_id,
                'name' => $bookingRequest->customer_name,
                'email' => $bookingRequest->customer_email,
                'phone' => $bookingRequest->customer_phone,
                'notes' => $bookingRequest->notes,
                'guests' => $bookingRequest->guests,
                'booking_date' => $bookingRequest->reservation_date?->toDateString(),
                'booking_time' => $bookingRequest->reservation_time,
                'total' => $bookingRequest->total,
                'source' => 'nova_front',
                'reference' => $bookingRequest->request_reference,
            ])
            ->throw()
            ->json() ?? [];
        $response = is_array($response) ? $response : [];

        return [
            'status' => 'created',
            'external_id' => $this->externalId($response, ['id', 'data.id', 'reservation.id', 'data.reservation.id', 'short_code']),
            'response' => $response,
            'error' => null,
        ];
    }

    /**
     * @return array{status: string, external_id: string|null, response: array<string, mixed>|null, error: string|null}
     */
    private function createWooTaxiRouteCheckout(ExternalSource $source, ExternalSyncMapping $mapping, PublicBookingRequest $bookingRequest): array
    {
        $isRouteRequest = in_array($bookingRequest->type, ['taxi', 'transfer'], true)
            || in_array($bookingRequest->booking_kind, ['taxi_route', 'tour_route'], true);

        if (! $isRouteRequest || $mapping->resource_type !== 'tour_route') {
            return [
                'status' => 'skipped',
                'external_id' => null,
                'response' => null,
                'error' => 'Woo route checkout creation only supports taxi requests and taxi routes.',
            ];
        }

        $pickupDateTime = $bookingRequest->pickup_date_time;

        if ($pickupDateTime === null && $bookingRequest->tour_date !== null && ! blank($bookingRequest->tour_schedule)) {
            $pickupDateTime = CarbonImmutable::parse(
                $bookingRequest->tour_date->toDateString().' '.$bookingRequest->tour_schedule,
                'Europe/Madrid',
            );
        }

        if ($pickupDateTime === null) {
            return [
                'status' => 'failed',
                'external_id' => null,
                'response' => null,
                'error' => 'Taxi route checkout requires pickup date and time.',
            ];
        }

        $path = (string) (data_get($source->settings, 'booking_create.path')
            ?: data_get($source->settings, 'sync_target.booking_create.path')
            ?: 'wp-json/taxilanz-mcp/v1/chauffeur/route-checkout');

        $response = $this->request($source)
            ->post($path, [
                'origin' => $bookingRequest->pickup_address ?: 'Origen indicado',
                'destination' => $bookingRequest->dropoff_address ?: $bookingRequest->service_name,
                'pickup_date' => $pickupDateTime->timezone('Europe/Madrid')->toDateString(),
                'pickup_time' => $pickupDateTime->timezone('Europe/Madrid')->format('H:i'),
                'passengers' => max(1, (int) ($bookingRequest->passengers ?? $bookingRequest->adults ?? 1)),
                'customer_name' => $bookingRequest->customer_name,
                'customer_phone' => $bookingRequest->customer_phone,
                'nova_route_token' => $bookingRequest->request_reference,
            ])
            ->throw()
            ->json() ?? [];
        $response = is_array($response) ? $response : [];

        $checkoutUrl = data_get($response, 'checkout_url');

        if (! blank($checkoutUrl)) {
            try {
                $this->stageWooTaxiRouteBookingLocally($source, $mapping, $bookingRequest, $response, $pickupDateTime);
            } catch (Throwable $exception) {
                Log::warning('Could not stage Woo taxi route booking locally', [
                    'source_id' => $source->id,
                    'server_id' => $source->server_id,
                    'external_id' => $bookingRequest->request_reference,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'status' => blank($checkoutUrl) ? 'failed' : 'created',
            'external_id' => $bookingRequest->request_reference,
            'response' => $response,
            'error' => blank($checkoutUrl) ? 'Taxilanz route checkout did not return checkout_url.' : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function stageWooTaxiRouteBookingLocally(
        ExternalSource $source,
        ExternalSyncMapping $serviceMapping,
        PublicBookingRequest $bookingRequest,
        array $response,
        CarbonImmutable $pickupDateTime,
    ): void {
        $bookingSource = ExternalSource::query()
            ->where('server_id', $source->server_id)
            ->where('source_platform', $source->source_platform)
            ->whereIn('resource_type', ['tour_booking', 'tour_route', 'taxi_booking'])
            ->latest('id')
            ->first();

        if (! $bookingSource) {
            return;
        }

        $adults = max(1, (int) ($bookingRequest->adults ?? $bookingRequest->passengers ?? 1));
        $children = max(0, (int) ($bookingRequest->children ?? 0));
        $amount = $bookingRequest->payment_amount_cents
            ? ((int) $bookingRequest->payment_amount_cents / 100)
            : (float) ($bookingRequest->base_price ?? 0);

        $raw = array_merge($response, [
            'id' => $bookingRequest->request_reference,
            'service_id' => (string) $serviceMapping->external_id,
            'status' => 'approved',
            'payment_status' => $bookingRequest->payment_status ?: 'pending',
            'unit_price' => $bookingRequest->base_price,
            'total' => $amount,
            'start_datetime' => $pickupDateTime->timezone('Europe/Madrid')->toDateTimeString(),
            'participants' => $adults,
            'adults' => $adults,
            'children' => $children,
            'pickup_address' => $bookingRequest->pickup_address,
            'dropoff_address' => $bookingRequest->dropoff_address ?: $bookingRequest->service_name,
        ]);

        $this->syncManager->upsertBooking($bookingSource, [
            'external_id' => $bookingRequest->request_reference,
            'external_item_id' => null,
            'booking_type' => 'taxilanz_route',
            'status' => 'approved',
            'payment_status' => $bookingRequest->payment_status ?: 'pending',
            'customer_name' => $bookingRequest->customer_name,
            'customer_email' => $bookingRequest->customer_email,
            'customer_phone' => $bookingRequest->customer_phone,
            'service_name' => $bookingRequest->service_name,
            'starts_at' => $pickupDateTime->timezone('Europe/Madrid')->toDateTimeString(),
            'ends_at' => null,
            'party_size' => $adults,
            'metadata' => ['raw' => $raw],
            'source_updated_at' => now(),
            'source_fingerprint' => sha1(json_encode(['woo_taxi_route_created', $bookingSource->id, $bookingRequest->request_reference])),
            'target_model' => 'tour_booking',
            'resource_type' => 'tour_route',
        ]);
    }

    /**
     * @return array{status: string, external_id: string|null, response: array<string, mixed>|null, error: string|null}
     */
    private function createLatePointBooking(ExternalSource $source, ExternalSyncMapping $mapping, PublicBookingRequest $bookingRequest): array
    {
        if ($bookingRequest->type !== 'tour') {
            return [
                'status' => 'skipped',
                'external_id' => null,
                'response' => null,
                'error' => 'LatePoint remote creation only supports tour visit requests.',
            ];
        }

        if (blank($bookingRequest->customer_email)) {
            return [
                'status' => 'failed',
                'external_id' => null,
                'response' => null,
                'error' => 'LatePoint requires a customer email to create bookings.',
            ];
        }

        $serviceId = (int) $mapping->external_id;
        $startDate = $bookingRequest->tour_date?->toDateString();
        $startTimeMinutes = $this->minutesFromTime($bookingRequest->tour_schedule);
        $endTimeMinutes = $startTimeMinutes !== null ? $startTimeMinutes + $this->latepointDurationMinutes($source, 60) : null;

        if (blank($startDate) || $startTimeMinutes === null) {
            return [
                'status' => 'failed',
                'external_id' => null,
                'response' => null,
                'error' => 'LatePoint booking requires a valid date and start time.',
            ];
        }

        $customerId = $this->latepointCustomerId($source, $bookingRequest);
        $childrenField = $this->latepointChildrenField($mapping);
        $childrenCount = max(0, (int) ($bookingRequest->children ?? 0));
        $agentId = $this->latepointAgentId($mapping);
        $locationId = $this->latepointLocationId($mapping) ?? 1;

        $path = (string) (data_get($source->settings, 'booking_create.path')
            ?: 'wp-json/wp-abilities/v1/abilities/latepoint/create-booking/run');

        $customFields = [];
        if (! blank($childrenField) && $childrenCount > 0) {
            $customFields[(string) $childrenField] = $childrenCount;
        }

        $input = [
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'start_date' => $startDate,
            'start_time' => $startTimeMinutes,
            'end_time' => $endTimeMinutes,
            'status' => 'approved',
            'notes' => $this->latepointBookingNotes($bookingRequest),
        ];
        if (! blank($agentId)) {
            $input['agent_id'] = (int) $agentId;
        }
        if (! blank($locationId)) {
            $input['location_id'] = (int) $locationId;
        }

        if ($customFields !== []) {
            $input['custom_fields'] = $customFields;
        }

        $response = $this->request($source)
            ->post($path, [
                'input' => $input,
            ])
            ->throw()
            ->json() ?? [];
        $response = is_array($response) ? $response : [];

        $externalId = $this->externalId($response, ['data.id', 'id', 'data.booking.id', 'booking.id']);

        if (! blank($externalId)) {
            try {
                $this->stageLatepointBookingLocally($source, $mapping, $bookingRequest, (string) $externalId, $response, $input);
            } catch (Throwable $exception) {
                // Non-fatal: remote booking already created.
                Log::warning('Could not stage LatePoint booking locally', [
                    'source_id' => $source->id,
                    'server_id' => $source->server_id,
                    'external_id' => (string) $externalId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'status' => 'created',
            'external_id' => $externalId,
            'response' => $response,
            'error' => null,
        ];
    }

    /**
     * Create/update an ExternalBooking (and project it) right after remote creation,
     * so it becomes visible in Filament without waiting for a sync.
     *
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $input
     */
    private function stageLatepointBookingLocally(
        ExternalSource $source,
        ExternalSyncMapping $serviceMapping,
        PublicBookingRequest $bookingRequest,
        string $externalId,
        array $response,
        array $input,
    ): void {
        $bookingSource = ExternalSource::query()
            ->where('server_id', $source->server_id)
            ->where('source_platform', $source->source_platform)
            ->where('target_model', 'tour_booking')
            ->latest('id')
            ->first();

        if (! $bookingSource) {
            return;
        }

        $startDate = (string) ($bookingRequest->tour_date?->toDateString() ?? '');
        $startsAt = null;
        $endsAt = null;

        if ($startDate !== '' && ! blank($bookingRequest->tour_schedule)) {
            $time = CarbonImmutable::parse($bookingRequest->tour_schedule)->format('H:i');
            $startsAt = CarbonImmutable::parse($startDate.' '.$time.':00');
            $endsAt = $startsAt->addMinutes($this->latepointDurationMinutes($source, 60));
        }

        $adults = max(1, (int) ($bookingRequest->adults ?? 1));
        $children = max(0, (int) ($bookingRequest->children ?? 0));
        $participants = $adults;

        $raw = (array) (data_get($response, 'data') ?: $response);
        $raw = array_merge($raw, [
            'id' => $externalId,
            'service_id' => (string) ($serviceMapping->external_id ?? $input['service_id'] ?? ''),
            'status' => $raw['status'] ?? ($input['status'] ?? 'approved'),
            'payment_status' => $bookingRequest->payment_status,
            'unit_price' => $bookingRequest->base_price,
            'total' => $bookingRequest->payment_amount_cents
                ? ((int) $bookingRequest->payment_amount_cents / 100)
                : null,
            'start_datetime' => $startsAt?->toDateTimeString(),
            'end_datetime' => $endsAt?->toDateTimeString(),
            'participants' => $participants,
            'adults' => $adults,
            'children' => $children,
        ]);

        $this->syncManager->upsertBooking($bookingSource, [
            'external_id' => $externalId,
            'external_item_id' => null,
            'booking_type' => 'latepoint',
            'status' => $raw['status'] ?? null,
            'payment_status' => $raw['payment_status'] ?? null,
            'customer_name' => $bookingRequest->customer_name,
            'customer_email' => $bookingRequest->customer_email,
            'customer_phone' => $bookingRequest->customer_phone,
            'service_name' => $bookingRequest->service_name,
            'starts_at' => $startsAt?->toDateTimeString(),
            'ends_at' => $endsAt?->toDateTimeString(),
            'party_size' => $participants,
            'metadata' => ['raw' => $raw],
            'source_updated_at' => now(),
            'source_fingerprint' => sha1(json_encode(['latepoint_booking_created', $bookingSource->id, $externalId])),
            'target_model' => 'tour_booking',
            'resource_type' => 'tour_booking',
        ]);
    }

    private function latepointChildrenField(ExternalSyncMapping $mapping): ?string
    {
        $raw = $this->latepointCatalogRaw($mapping);
        $field = data_get($raw, 'children_field');

        return blank($field) ? null : (string) $field;
    }

    private function latepointAgentId(ExternalSyncMapping $mapping): ?int
    {
        $raw = $this->latepointCatalogRaw($mapping);
        $value = data_get($raw, 'agent_id');

        return blank($value) ? null : (int) $value;
    }

    private function latepointLocationId(ExternalSyncMapping $mapping): ?int
    {
        $raw = $this->latepointCatalogRaw($mapping);
        $value = data_get($raw, 'location_id');

        return blank($value) ? null : (int) $value;
    }

    /**
     * Resolve the synced LatePoint service payload stored locally for this mapping.
     *
     * @return array<string, mixed>
     */
    private function latepointCatalogRaw(ExternalSyncMapping $mapping): array
    {
        $externalSourceId = $mapping->external_source_id;
        $externalId = (string) $mapping->external_id;

        if (blank($externalSourceId) || blank($externalId)) {
            return [];
        }

        $item = ExternalCatalogItem::query()
            ->where('external_source_id', $externalSourceId)
            ->where('external_id', $externalId)
            ->latest('id')
            ->first();

        $raw = data_get($item?->metadata, 'raw', []);

        return is_array($raw) ? $raw : [];
    }

    private function latepointCustomerId(ExternalSource $source, PublicBookingRequest $bookingRequest): int
    {
        $email = (string) $bookingRequest->customer_email;
        $path = 'wp-json/wp-abilities/v1/abilities/latepoint/get-customer-by-email/run';

        try {
            $response = $this->request($source)
                ->post($path, ['input' => ['email' => $email]])
                ->throw()
                ->json() ?? [];
            $response = is_array($response) ? $response : [];

            $id = data_get($response, 'data.id') ?? data_get($response, 'id');
            if (! blank($id)) {
                return (int) $id;
            }
        } catch (Throwable) {
            // fall through to create-customer
        }

        $name = trim((string) $bookingRequest->customer_name);
        [$firstName, $lastName] = $this->splitName($name);

        $create = $this->request($source)
            ->post('wp-json/wp-abilities/v1/abilities/latepoint/create-customer/run', [
                'input' => [
                    'first_name' => $firstName ?: 'Guest',
                    'last_name' => $lastName ?: null,
                    'email' => $email,
                    'phone' => $bookingRequest->customer_phone,
                ],
            ])
            ->throw()
            ->json() ?? [];
        $create = is_array($create) ? $create : [];

        $id = data_get($create, 'data.id') ?? data_get($create, 'id');

        return (int) $id;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));

        if ($parts === []) {
            return ['', ''];
        }

        $first = array_shift($parts);
        $last = trim(implode(' ', $parts));

        return [(string) $first, $last];
    }

    private function minutesFromTime(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        $time = (string) $value;
        $time = str_contains($time, 'T') ? CarbonImmutable::parse($time)->format('H:i') : $time;
        $time = trim($time);

        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return null;
        }

        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return null;
        }

        return ($hours * 60) + $minutes;
    }

    private function latepointDurationMinutes(ExternalSource $source, int $fallback): int
    {
        $duration = data_get($source->settings, 'sync_target.duration')
            ?: data_get($source->settings, 'duration')
            ?: $fallback;

        $duration = (int) $duration;

        return $duration > 0 ? $duration : $fallback;
    }

    private function latepointBookingNotes(PublicBookingRequest $bookingRequest): string
    {
        $notes = trim((string) ($bookingRequest->notes ?? ''));

        $adults = max(1, (int) ($bookingRequest->adults ?? 1));
        $children = max(0, (int) ($bookingRequest->children ?? 0));

        $extras = [
            'Reference: '.$bookingRequest->request_reference,
            'Adults: '.$adults,
        ];

        if ($children > 0) {
            $extras[] = 'Children: '.$children;
        }

        $extras[] = 'Total attendees: '.$adults;

        $suffix = implode("\n", $extras);

        if ($notes === '') {
            return $suffix;
        }

        return Str::limit($notes."\n".$suffix, 1000, '');
    }

    private function request(ExternalSource $source): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) ($source->api_url ?: $source->base_url), '/'))
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30);

        $host = parse_url((string) ($source->api_url ?: $source->base_url), PHP_URL_HOST);

        if (is_string($host) && str_ends_with($host, '.test')) {
            $request = $request->withoutVerifying();
        }

        $token = $this->bearerTokenFor($source);

        if (! blank($token)) {
            $request = $request->withToken($token);
        }

        $localHeader = data_get($source->settings, 'local_header')
            ?: data_get($source->server?->metadata, 'local_header');

        $localHeaderName = data_get($localHeader, 'name');
        $localHeaderValue = data_get($localHeader, 'value')
            ?: $this->envValue(data_get($localHeader, 'env'));

        if (! blank($localHeaderName) && ! blank($localHeaderValue)) {
            $request = $request->withHeaders([(string) $localHeaderName => (string) $localHeaderValue]);
        }

        return $request;
    }

    private function bearerTokenFor(ExternalSource $source): ?string
    {
        $token = $this->envValue(data_get($source->settings, 'auth_token_env'))
            ?: $this->envValue(data_get($source->server?->metadata, 'auth_token_env'))
            ?: data_get($source->credentials, 'access_token');

        return blank($token) ? null : (string) $token;
    }

    private function envValue(mixed $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        $value = env((string) $name);

        return blank($value) ? null : (string) $value;
    }

    /**
     * @param  array<string, mixed>|null  $response
     * @param  array<int, string>  $paths
     */
    private function externalId(?array $response, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($response, $path);

            if (! blank($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  array{status: string, source_platform?: string|null, source_label?: string|null, external_id?: string|null, response?: array<string, mixed>|null, error?: string|null}  $result
     * @return array{status: string, source_platform?: string|null, source_label?: string|null, external_id?: string|null, error?: string|null}
     */
    private function persist(PublicBookingRequest $bookingRequest, array $result): array
    {
        $bookingRequest->forceFill([
            'remote_booking_status' => $result['status'],
            'remote_source_platform' => $result['source_platform'] ?? null,
            'remote_source_label' => $result['source_label'] ?? null,
            'remote_external_id' => $result['external_id'] ?? null,
            'remote_response' => $result['response'] ?? null,
            'remote_error' => $result['error'] ?? null,
        ])->save();

        return [
            'status' => $result['status'],
            'source_platform' => $result['source_platform'] ?? null,
            'source_label' => $result['source_label'] ?? null,
            'external_id' => $result['external_id'] ?? null,
            'checkout_url' => data_get($result, 'response.checkout_url'),
            'error' => $result['error'] ?? null,
        ];
    }
}
