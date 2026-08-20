<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class NovaAssignTaxilanzTariffZones extends Command
{
    protected $signature = 'nova:assign-taxilanz-tariff-zones
                            {--dry-run : Preview assignments without saving}
                            {--force : Overwrite hotels that already have a tariff_zone}';

    protected $description = 'Assign Taxilanz transfer tariff zones to hotels and locations using name, address, city, lat/lng';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $updatedHotels = 0;
        $skippedHotels = 0;
        $updatedLocations = 0;

        $hotelQuery = Hotel::query();

        if (! $force) {
            $hotelQuery->whereNull('tariff_zone');
        }

        $hotelQuery->chunkById(100, function ($hotels) use (&$updatedHotels, &$skippedHotels, $dryRun): void {
            foreach ($hotels as $hotel) {
                $zone = $this->zoneForHotel($hotel);

                if ($zone === null) {
                    $skippedHotels++;
                    $this->line("  SKIP  {$hotel->name}");

                    continue;
                }

                $this->line("  OK    {$hotel->name} → {$zone}");

                if (! $dryRun) {
                    $hotel->forceFill(['tariff_zone' => $zone])->save();
                }

                $updatedHotels++;
            }
        });

        $locationQuery = Location::query();

        if (! $force) {
            $locationQuery->whereNull('tariff_zone');
        }

        $locationQuery->chunkById(100, function ($locations) use (&$updatedLocations, $dryRun): void {
            foreach ($locations as $location) {
                $zone = $this->zoneByText((string) $location->name, '', '');

                if ($zone === null) {
                    continue;
                }

                if (! $dryRun) {
                    $location->forceFill(['tariff_zone' => $zone])->save();
                }

                $updatedLocations++;
            }
        });

        $label = $dryRun ? '[DRY RUN] Would assign' : 'Assigned';
        $this->info("{$label} tariff zones to {$updatedHotels} hotels ({$skippedHotels} skipped) and {$updatedLocations} locations.");

        return self::SUCCESS;
    }

    private function zoneForHotel(Hotel $hotel): ?string
    {
        $name = (string) ($hotel->name ?? '');
        $address = (string) ($hotel->address ?? '');
        $city = (string) ($hotel->city ?? '');
        $lat = (float) ($hotel->lat ?: $hotel->latitude ?? 0);
        $lng = (float) ($hotel->lng ?: $hotel->longitude ?? 0);

        $zone = $this->zoneByText($name, $address, $city);

        if ($zone !== null) {
            return $zone;
        }

        if ($lat !== 0.0 && $lng !== 0.0) {
            return $this->zoneByCoordinates($lat, $lng);
        }

        return $this->zoneByLowConfidenceText($name, $address, $city);
    }

    /**
     * High-confidence rules: multi-word or very specific names that won't generate
     * false positives on municipality names (e.g. "Tías" municipality ≠ "tias" resort zone,
     * "Teguise" municipality ≠ "Costa Teguise" resort zone).
     * "ace" removed from aeropuerto because it matches "palace", "race", etc.
     *
     * @return array<string, string[]>
     */
    private function highConfidenceRules(): array
    {
        return [
            'aeropuerto' => ['aeropuerto', 'airport'],
            'la santa sport' => ['la santa sport', 'club la santa'],
            'costa teguise' => ['costa teguise', 'beatriz costa', 'costa bastian', 'lanzarote gardens'],
            'playa blanca' => ['playa blanca', 'princesa yaiza', 'rubicon', 'rubikon', 'gran castillo', 'sun tropical'],
            'puerto del carmen' => ['puerto del carmen', 'puerto carmen', 'fariones', 'jameos playa'],
            'playa honda' => ['playa honda'],
            'playa quemada' => ['playa quemada'],
            'matagorda' => ['matagorda', 'beatriz playa', 'costa sal', 'nautilus lanzarote'],
            'jardin del cactus' => ['jardin del cactus'],
            'jameos del agua' => ['jameos del agua'],
            'caleta de famara' => ['caleta de famara', 'famara'],
            'mirador del rio' => ['mirador del rio'],
            'los marmoles' => ['marmoles'],
            'la marina' => ['la marina'],
            'puerto calero' => ['puerto calero'],
            'la geria' => ['la geria'],
            'el golfo' => ['el golfo'],
            'timanfaya' => ['timanfaya'],
            'la santa' => ['la santa'],
            'orzola' => ['orzola'],
            'yaiza' => ['yaiza'],
            'haria' => ['haria'],
            'tinajo' => ['tinajo'],
            'arrecife' => ['arrecife'],
        ];
    }

    /**
     * Low-confidence rules: single words that are also municipality names.
     * These are tried AFTER proximity matching to avoid assigning municipality
     * to hotels in resort sub-zones (e.g. Tías municipality → Puerto del Carmen resort).
     *
     * @return array<string, string[]>
     */
    private function lowConfidenceRules(): array
    {
        return [
            'costa teguise' => ['teguise'],
            'tias' => ['tias'],
        ];
    }

    private function zoneByText(string $name, string $address, string $city = ''): ?string
    {
        $nameOnly = $this->normalize($name);
        $nameAddr = $this->normalize($name.' '.$address);

        foreach ($this->highConfidenceRules() as $zone => $needles) {
            foreach ($needles as $needle) {
                $n = $this->normalize($needle);
                if (str_word_count($n) >= 2 && str_contains($nameOnly, $n)) {
                    return $zone;
                }
            }
        }

        foreach ($this->highConfidenceRules() as $zone => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($nameAddr, $this->normalize($needle))) {
                    return $zone;
                }
            }
        }

        return null;
    }

    private function zoneByLowConfidenceText(string $name, string $address, string $city): ?string
    {
        $nameAddr = $this->normalize($name.' '.$address);
        $cityNorm = $this->normalize($city);

        foreach ($this->lowConfidenceRules() as $zone => $needles) {
            foreach ($needles as $needle) {
                $n = $this->normalize($needle);
                if (str_contains($nameAddr, $n) || str_contains($cityNorm, $n)) {
                    return $zone;
                }
            }
        }

        return null;
    }

    /**
     * Approximate zone centroids (lat, lng) for proximity fallback.
     *
     * @return array<string, array{float, float}>
     */
    private function zoneCentroids(): array
    {
        return [
            'aeropuerto' => [28.9452, -13.6052],
            'arrecife' => [28.9638, -13.5477],
            'caleta de famara' => [29.1056, -13.5694],
            'costa teguise' => [28.9880, -13.5030],
            'el golfo' => [28.9846, -13.8306],
            'haria' => [29.1484, -13.5236],
            'jameos del agua' => [29.1627, -13.4305],
            'jardin del cactus' => [29.0875, -13.5577],
            'la geria' => [28.9806, -13.7022],
            'la marina' => [28.9624, -13.5250],
            'la santa sport' => [29.0608, -13.7272],
            'la santa' => [29.0597, -13.7153],
            'los marmoles' => [28.9767, -13.5180],
            'matagorda' => [28.9339, -13.6335],
            'mirador del rio' => [29.2005, -13.4814],
            'orzola' => [29.2097, -13.4499],
            'playa blanca' => [28.8637, -13.8439],
            'playa honda' => [28.9390, -13.5872],
            'playa quemada' => [28.8956, -13.7625],
            'puerto calero' => [28.8936, -13.6936],
            'puerto del carmen' => [28.9210, -13.6570],
            'teguise' => [29.0648, -13.5519],
            'tias' => [28.9622, -13.6633],
            'timanfaya' => [29.0155, -13.7712],
            'tinajo' => [29.0689, -13.6672],
            'yaiza' => [28.9484, -13.7757],
        ];
    }

    private function zoneByCoordinates(float $lat, float $lng): ?string
    {
        $nearest = null;
        $minDist = PHP_FLOAT_MAX;

        foreach ($this->zoneCentroids() as $zone => [$zoneLat, $zoneLng]) {
            $dist = $this->haversineKm($lat, $lng, $zoneLat, $zoneLng);

            if ($dist < $minDist) {
                $minDist = $dist;
                $nearest = $zone;
            }
        }

        return ($nearest !== null && $minDist <= 15.0) ? $nearest : null;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2.0 * asin(sqrt($a));
    }

    private function normalize(string $value): string
    {
        $value = Str::lower($value);
        $value = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $value);
        $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
