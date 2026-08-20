<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaBusiness;
use App\Models\Server;
use App\Models\Tool;
use Illuminate\Console\Command;

final class NovaSeedTaxilanzTransfersMcp extends Command
{
    protected $signature = 'nova:seed-taxilanz-transfers-mcp';

    protected $description = 'Seed the Taxilanz Transfers dynamic MCP server and tools';

    public function handle(): int
    {
        $business = NovaBusiness::query()
            ->whereIn('slug', ['taxilanz', 'taxilanz-hoteles'])
            ->orWhereIn('name', ['Taxilanz', 'Taxilanz Hoteles'])
            ->orderByRaw("slug = 'taxilanz' desc")
            ->first();

        $server = Server::query()->updateOrCreate(
            ['slug' => 'taxilanz-transfers'],
            [
                'nova_business_id' => $business?->id,
                'name' => 'Taxilanz Transfers MCP',
                'description' => 'Transfer tools for hotel/location search, price estimates and booking payloads.',
                'version' => '1.0.0',
                'transport' => 'web',
                'endpoint' => '/mcp/taxilanz-transfers',
                'is_active' => true,
                'metadata' => [
                    'domain' => 'taxilanz_transfers',
                    'source' => 'nova-seed-taxilanz-transfers-mcp',
                ],
            ],
        );

        foreach ($this->tools() as $index => $definition) {
            Tool::query()->updateOrCreate(
                [
                    'server_id' => $server->id,
                    'name' => $definition['name'],
                ],
                [
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'input_schema' => $definition['input_schema'],
                    'output_schema' => $definition['output_schema'] ?? null,
                    'handler_code' => $definition['handler_code'],
                    'annotations' => $definition['annotations'] ?? [],
                    'metadata' => $definition['metadata'] ?? ['source' => 'nova-seed-taxilanz-transfers-mcp'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        $this->info("Seeded {$server->name} at {$server->endpoint}");
        $this->line('Tools: '.implode(', ', array_column($this->tools(), 'name')));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tools(): array
    {
        return [
            [
                'name' => 'transfer_locations',
                'title' => 'Transfer Locations',
                'description' => 'Search imported hotels and locations usable as transfer pickup/dropoff points.',
                'input_schema' => [
                    'query' => ['type' => 'string', 'description' => 'Hotel or location search text.'],
                    'limit' => ['type' => 'integer', 'description' => 'Maximum results.', 'default' => 10],
                ],
                'handler_code' => <<<'PHP'
$query = trim((string) ($input['query'] ?? ''));
$limit = max(1, min(50, (int) ($input['limit'] ?? 10)));

$hotels = \App\Models\Hotel::query()
    ->with('location.city.country')
    ->when($query !== '', fn ($builder) => $builder->where('name', 'like', '%'.$query.'%'))
    ->orderBy('name')
    ->limit($limit)
    ->get()
    ->map(fn ($hotel) => [
        'type' => 'hotel',
        'id' => $hotel->id,
        'name' => $hotel->name,
        'location_id' => $hotel->location_id,
        'tariff_zone' => $hotel->tariff_zone ?? $hotel->location?->tariff_zone,
        'latitude' => $hotel->location?->latitude,
        'longitude' => $hotel->location?->longitude,
        'address' => $hotel->location?->full_address,
    ]);

$remaining = max(0, $limit - $hotels->count());
$locations = collect();

if ($remaining > 0) {
    $locations = \App\Models\Location::query()
        ->with('city.country')
        ->when($query !== '', fn ($builder) => $builder->where('name', 'like', '%'.$query.'%'))
        ->orderBy('name')
        ->limit($remaining)
        ->get()
        ->map(fn ($location) => [
            'type' => 'location',
            'id' => $location->id,
            'name' => $location->name,
            'location_id' => $location->id,
            'tariff_zone' => $location->tariff_zone,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'address' => $location->full_address,
        ]);
}

return json_encode([
    'query' => $query,
    'count' => $hotels->count() + $locations->count(),
    'results' => $hotels->concat($locations)->values()->all(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
PHP,
            ],
            [
                'name' => 'transfer_price_estimate',
                'title' => 'Transfer Price Estimate',
                'description' => 'Estimate transfer price between two imported hotels or locations.',
                'input_schema' => [
                    'pickup_location' => ['type' => 'string', 'description' => 'Pickup hotel/location name.', 'required' => true],
                    'dropoff_location' => ['type' => 'string', 'description' => 'Dropoff hotel/location name.', 'required' => true],
                    'passengers' => ['type' => 'integer', 'description' => 'Passenger count.', 'default' => 1],
                ],
                'handler_code' => <<<'PHP'
$pickupName = trim((string) ($input['pickup_location'] ?? ''));
$dropoffName = trim((string) ($input['dropoff_location'] ?? ''));
$passengers = max(1, (int) ($input['passengers'] ?? 1));

$resolve = function (string $name): ?array {
    $terms = array_values(array_filter(array_unique([
        $name,
        trim(str_ireplace(['hotel ', 'apartamentos ', 'apartamento '], '', $name)),
    ])));
    $words = array_values(array_filter(explode(' ', str_replace(['-', ',', '.'], ' ', $terms[1] ?? $name)), fn ($word) => strlen($word) >= 4));

    $locationQuery = \App\Models\Location::query()
        ->where(function ($builder) use ($terms, $words) {
            foreach ($terms as $term) {
                $builder->orWhere('name', 'like', '%'.$term.'%');
            }

            foreach ($words as $word) {
                $builder->orWhere('name', 'like', '%'.$word.'%');
            }
        })
        ->orderByRaw('name = ? desc', [$name])
        ->orderByRaw('name like ? desc', [($terms[1] ?? $name).'%'])
        ->orderBy('name');

    $exactLocation = (clone $locationQuery)
        ->whereNotNull('tariff_zone')
        ->first();

    if ($exactLocation && strcasecmp((string) $exactLocation->name, $name) === 0) {
        return [
            'type' => 'location',
            'id' => $exactLocation->id,
            'name' => $exactLocation->name,
            'location_id' => $exactLocation->id,
            'tariff_zone' => $exactLocation->tariff_zone,
            'latitude' => $exactLocation->latitude,
            'longitude' => $exactLocation->longitude,
        ];
    }

    if (str_contains(strtolower($name), 'aeropuerto') || str_contains(strtolower($name), 'airport')) {
        $location = $locationQuery->first();

        if ($location) {
            return [
                'type' => 'location',
                'id' => $location->id,
                'name' => $location->name,
                'location_id' => $location->id,
                'tariff_zone' => $location->tariff_zone,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
            ];
        }
    }

    $hotel = \App\Models\Hotel::query()
        ->with('location')
        ->where(function ($builder) use ($terms, $words) {
            foreach ($terms as $term) {
                $builder->orWhere('name', 'like', '%'.$term.'%');
            }

            foreach ($words as $word) {
                $builder->orWhere('name', 'like', '%'.$word.'%');
            }
        })
        ->orderByRaw('name = ? desc', [$name])
        ->orderByRaw('name like ? desc', [($terms[1] ?? $name).'%'])
        ->orderByRaw('name like ? desc', ['%'.($terms[1] ?? $name).'%'])
        ->orderBy('name')
        ->first();

    if ($hotel) {
        return [
            'type' => 'hotel',
            'id' => $hotel->id,
            'name' => $hotel->name,
            'location_id' => $hotel->location_id,
            'tariff_zone' => $hotel->tariff_zone ?? $hotel->location?->tariff_zone,
            'latitude' => $hotel->location?->latitude,
            'longitude' => $hotel->location?->longitude,
        ];
    }

    $location = $locationQuery->first();

    if (! $location) {
        return null;
    }

    return [
        'type' => 'location',
        'id' => $location->id,
        'name' => $location->name,
        'location_id' => $location->id,
        'tariff_zone' => $location->tariff_zone,
        'latitude' => $location->latitude,
        'longitude' => $location->longitude,
    ];
};

$pickup = $resolve($pickupName);
$dropoff = $resolve($dropoffName);

if (! $pickup || ! $dropoff) {
    return json_encode([
        'ok' => false,
        'error' => 'Pickup or dropoff location was not found.',
        'pickup_found' => (bool) $pickup,
        'dropoff_found' => (bool) $dropoff,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

$distanceKm = null;
$fixedFare = null;
$fixedFareRoute = null;

$normalize = function (string $value): string {
    $value = strtolower(trim($value));
    $value = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $value);
    $value = str_replace(['hotel ', 'apartamentos ', 'apartamento ', 'cesar manrique', 'ferry a la graciosa'], '', $value);
    $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return trim($value);
};

$aliases = [
    'aeropuerto de lanzarote' => 'aeropuerto',
    'aeropuerto lanzarote' => 'aeropuerto',
    'lanzarote airport' => 'aeropuerto',
    'ace' => 'aeropuerto',
    'beatriz costa' => 'costa teguise',
    'beatriz costa teguise' => 'costa teguise',
    'beatriz playa' => 'matagorda',
    'hotel beatriz playa' => 'matagorda',
    'princesa yaiza' => 'playa blanca',
    'matagorda' => 'matagorda',
    'playa blanca' => 'playa blanca',
    'puerto del carmen' => 'puerto del carmen',
    'costa teguise' => 'costa teguise',
    'haria' => 'haria',
    'haría' => 'haria',
    'tinajo' => 'tinajo',
    'la santa sport' => 'la santa sport',
    'la santa' => 'la santa',
    'los marmoles' => 'los marmoles',
    'la marina' => 'la marina',
    'jardin del cactus' => 'jardin del cactus',
    'el jardin del cactus' => 'jardin del cactus',
    'jameos del agua' => 'jameos del agua',
    'caleta de famara' => 'caleta de famara',
    'famara' => 'caleta de famara',
    'mirador del rio' => 'mirador del rio',
    'orzola' => 'orzola',
    'órzola' => 'orzola',
    'el golfo' => 'el golfo',
    'la geria' => 'la geria',
    'pn del timanfaya' => 'timanfaya',
    'p n del timanfaya' => 'timanfaya',
    'timanfaya' => 'timanfaya',
    'yaiza' => 'yaiza',
    'playa quemada' => 'playa quemada',
    'puerto calero' => 'puerto calero',
    'arrecife' => 'arrecife',
    'teguise' => 'teguise',
    'playa honda' => 'playa honda',
    'tias' => 'tias',
];

$canonical = function (string $value) use ($normalize, $aliases): string {
    $normalized = $normalize($value);

    if (isset($aliases[$normalized])) {
        return $aliases[$normalized];
    }

    foreach ($aliases as $alias => $canonical) {
        if (str_contains($normalized, $alias)) {
            return $canonical;
        }
    }

    return $normalized;
};

$pickupZone = (string) ($pickup['tariff_zone'] ?? '') !== '' ? (string) $pickup['tariff_zone'] : $canonical((string) ($pickup['name'] ?? $pickupName));
$dropoffZone = (string) ($dropoff['tariff_zone'] ?? '') !== '' ? (string) $dropoff['tariff_zone'] : $canonical((string) ($dropoff['name'] ?? $dropoffName));

$tariff = \App\Models\TransferTariff::query()
    ->where('is_active', true)
    ->where('origin_zone', $pickupZone)
    ->where('destination_zone', $dropoffZone)
    ->first();

if (! $tariff) {
    $tariff = \App\Models\TransferTariff::query()
        ->where('is_active', true)
        ->where('origin_zone', $dropoffZone)
        ->where('destination_zone', $pickupZone)
        ->first();
}

if ($tariff && $tariff->origin_zone === $pickupZone) {
    $fixedFare = (float) $tariff->price;
    $fixedFareRoute = $pickupZone.' -> '.$dropoffZone;
} elseif ($tariff) {
    $fixedFare = (float) $tariff->price;
    $fixedFareRoute = $dropoffZone.' -> '.$pickupZone;
}

if ($pickup['latitude'] && $pickup['longitude'] && $dropoff['latitude'] && $dropoff['longitude']) {
    $earthRadius = 6371;
    $latFrom = deg2rad((float) $pickup['latitude']);
    $lonFrom = deg2rad((float) $pickup['longitude']);
    $latTo = deg2rad((float) $dropoff['latitude']);
    $lonTo = deg2rad((float) $dropoff['longitude']);
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    $distanceKm = round($angle * $earthRadius, 1);
}

$base = 12.00;
$perKm = 1.45;
$passengerSurcharge = max(0, $passengers - 4) * 6.00;
$estimated = $fixedFare !== null ? round($fixedFare + $passengerSurcharge, 2) : ($distanceKm ? round($base + ($distanceKm * $perKm) + $passengerSurcharge, 2) : null);

return json_encode([
    'ok' => true,
    'pickup' => $pickup,
    'dropoff' => $dropoff,
    'passengers' => $passengers,
    'distance_km' => $distanceKm,
    'currency' => 'EUR',
    'estimated_price' => $estimated,
    'fixed_fare_route' => $fixedFareRoute,
    'pickup_tariff_zone' => $pickupZone,
    'dropoff_tariff_zone' => $dropoffZone,
    'holiday_surcharge_percent' => 15,
    'igic_percent' => $tariff?->igic_percent ?? 7,
    'igic_included' => (bool) ($tariff?->igic_included ?? false),
    'pricing_basis' => $fixedFare !== null ? 'taxilanz_fixed_fare_table' : ($distanceKm ? 'haversine_local_estimate' : 'missing_coordinates'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
PHP,
            ],
            [
                'name' => 'transfer_booking_payload',
                'title' => 'Transfer Booking Payload',
                'description' => 'Prepare a normalized transfer booking payload after origin/destination selection.',
                'input_schema' => [
                    'customer_name' => ['type' => 'string', 'required' => true],
                    'customer_email' => ['type' => 'string'],
                    'customer_phone' => ['type' => 'string'],
                    'pickup_location' => ['type' => 'string', 'required' => true],
                    'dropoff_location' => ['type' => 'string', 'required' => true],
                    'pickup_date' => ['type' => 'string', 'required' => true],
                    'pickup_time' => ['type' => 'string', 'required' => true],
                    'passengers' => ['type' => 'integer', 'default' => 1],
                    'amount' => ['type' => 'number'],
                ],
                'handler_code' => <<<'PHP'
$payload = [
    'type' => 'transfer',
    'customer_name' => trim((string) ($input['customer_name'] ?? '')),
    'customer_email' => trim((string) ($input['customer_email'] ?? '')),
    'customer_phone' => trim((string) ($input['customer_phone'] ?? '')),
    'origin' => trim((string) ($input['pickup_location'] ?? '')),
    'destination' => trim((string) ($input['dropoff_location'] ?? '')),
    'pickup_date' => trim((string) ($input['pickup_date'] ?? '')),
    'pickup_time' => trim((string) ($input['pickup_time'] ?? '')),
    'passengers' => max(1, (int) ($input['passengers'] ?? 1)),
    'amount' => isset($input['amount']) && is_numeric($input['amount']) ? (float) $input['amount'] : null,
    'currency' => 'EUR',
    'source' => 'taxilanz-transfers-mcp',
];

$payload['ready_for_payment'] = $payload['customer_name'] !== ''
    && $payload['origin'] !== ''
    && $payload['destination'] !== ''
    && $payload['pickup_date'] !== ''
    && $payload['pickup_time'] !== '';

return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
PHP,
            ],
        ];
    }
}
