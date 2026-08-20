<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Models\ExternalCatalogItem;
use App\Models\ExternalSource;
use App\Models\Hotel;
use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class NovaImportTaxilanzHotels extends Command
{
    protected $signature = 'nova:import-taxilanz-hotels {--source-id=31 : External source id for Taxilanz Hoteles} {--path=/Users/patrickms/Downloads/taxilanzhrnew : Local Taxilanz Hoteles project path}';

    protected $description = 'Import all Taxilanz Hoteles users as Nova hotels and locations';

    public function handle(): int
    {
        $source = ExternalSource::query()->findOrFail((int) $this->option('source-id'));
        $env = $this->readEnv((string) $this->option('path'));

        config()->set('database.connections.taxilanz_hoteles_import', [
            'driver' => 'mysql',
            'host' => $env['DB_HOST'] ?? '127.0.0.1',
            'port' => $env['DB_PORT'] ?? '3306',
            'database' => $env['DB_DATABASE'] ?? null,
            'username' => $env['DB_USERNAME'] ?? null,
            'password' => $env['DB_PASSWORD'] ?? null,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ]);

        DB::purge('taxilanz_hoteles_import');

        $country = Country::query()->firstOrCreate(
            ['code' => 'ES'],
            ['name' => 'Spain', 'continent_code' => 'EU', 'phone_code' => '+34', 'is_active' => true],
        );

        $processed = 0;
        $skipped = 0;

        DB::connection('taxilanz_hoteles_import')
            ->table('usuarios')
            ->where('tipo_id', 2)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($source, $country, &$processed, &$skipped): void {
                foreach ($users as $user) {
                    $name = trim((string) ($user->nombre ?? ''));

                    if ($name === '') {
                        $skipped++;

                        continue;
                    }

                    $cityName = trim((string) ($user->poblacion ?? '')) ?: 'Lanzarote';
                    $city = City::query()->firstOrCreate(
                        ['country_id' => $country->id, 'name' => $cityName],
                        ['is_popular' => $cityName === 'Lanzarote'],
                    );

                    $address = collect([$user->direccion ?? null, $cityName, 'Spain'])->filter()->join(', ');
                    $location = Location::query()->updateOrCreate(
                        ['city_id' => $city->id, 'name' => $name],
                        [
                            'address' => $user->direccion ?: null,
                            'description' => 'Taxilanz hotel pickup/dropoff point.',
                            'latitude' => null,
                            'longitude' => null,
                            'is_popular' => false,
                        ],
                    );

                    Hotel::query()->updateOrCreate(
                        ['name' => $name],
                        [
                            'location_id' => $location->id,
                            'description' => 'Imported from Taxilanz Hoteles.',
                            'phone' => $user->tel_fijo ?: null,
                            'email' => $user->email ?: null,
                            'is_active' => (int) ($user->estado_id ?? 1) === 1,
                            'is_featured' => false,
                        ],
                    );

                    ExternalCatalogItem::query()->updateOrCreate(
                        [
                            'external_source_id' => $source->id,
                            'external_id' => (string) $user->id,
                            'type' => 'hotel',
                        ],
                        [
                            'server_id' => $source->server_id,
                            'source_platform' => $source->source_platform,
                            'source_label' => $source->source_label,
                            'business_name' => $source->business_name,
                            'status' => (int) ($user->estado_id ?? 1) === 1 ? 'active' : 'inactive',
                            'name' => $name,
                            'description' => 'Imported from Taxilanz Hoteles.',
                            'short_description' => $address,
                            'metadata' => [
                                'raw' => [
                                    'id' => $user->id,
                                    'name' => $name,
                                    'address' => $address,
                                    'city' => $cityName,
                                    'country' => 'Spain',
                                    'phone' => $user->tel_fijo ?? null,
                                    'email' => $user->email ?? null,
                                ],
                            ],
                            'source_updated_at' => $user->updated_at ?? now(),
                            'source_fingerprint' => sha1(json_encode(['taxilanz_hotel', $user->id, $name, $user->updated_at ?? null])),
                        ],
                    );

                    $processed++;
                }
            });

        $this->info("Imported {$processed} Taxilanz hotels. Skipped {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function readEnv(string $path): array
    {
        $file = rtrim($path, '/').'/.env';

        if (! is_file($file)) {
            return [];
        }

        $values = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim((string) $line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim(trim($value), '"\'');
        }

        return $values;
    }
}
