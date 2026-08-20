<?php

namespace App\Console\Commands;

use App\Models\Taxi\Municipio;
use App\Models\Taxi\Usuario;
use App\Models\Taxi\Hotel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncHotelesFromApi extends Command
{
    protected $signature = 'app:sync-hoteles-from-api
                            {--municipio= : Código de municipio a sincronizar (si no se indica, sincroniza todos)}
                            {--dry-run : Muestra los cambios sin aplicarlos}';

    protected $description = 'Sincroniza hoteles desde la API externa de taxisnorteysur.com a la tabla usuarios_direcciones';

    private const API_BASE_URL = 'https://www.taxisnorteysur.com/v2/services/usuarios.php';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $municipioFilter = $this->option('municipio');

        if ($dryRun) {
            $this->components->warn('Modo dry-run activado. No se aplicarán cambios.');
        }

        $municipios = $this->resolveMunicipios($municipioFilter);

        if ($municipios->isEmpty()) {
            $this->components->error('No se encontraron municipios para sincronizar.');

            return self::FAILURE;
        }

        $this->components->info(sprintf('Sincronizando hoteles de %d municipio(s)...', $municipios->count()));

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($municipios as $municipio) {
            $this->components->twoColumnDetail($municipio->nombre, "cod: {$municipio->id}");

            $hotels = $this->fetchHotelsForMunicipio($municipio->id);

            if ($hotels === null) {
                $this->components->error("  Error al obtener hoteles para {$municipio->nombre}");
                $skipped++;

                continue;
            }

            foreach ($hotels as $hotel) {
                $result = $this->syncHotel($hotel, $dryRun);

                match ($result) {
                    'created' => $created++,
                    'updated' => $updated++,
                    default => $skipped++,
                };
            }
        }

        $this->newLine();
        $this->components->info('Sincronización completada.');
        $this->components->twoColumnDetail('Creados', (string) $created);
        $this->components->twoColumnDetail('Actualizados', (string) $updated);
        $this->components->twoColumnDetail('Sin cambios', (string) $skipped);

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Municipio>
     */
    private function resolveMunicipios(?string $filter): \Illuminate\Database\Eloquent\Collection
    {
        $query = Municipio::query();

        if (filled($filter)) {
            $query->where('id', $filter);
        }

        return $query->get();
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchHotelsForMunicipio(int $codMunicipio): ?array
    {
        $response = Http::timeout(15)->get(self::API_BASE_URL, [
            'type' => 'read_hoteles',
            's_codmunicipio' => $codMunicipio,
        ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json('data', []);
    }

    /**
     * @param array<string, mixed> $apiHotel
     */
    private function syncHotel(array $apiHotel, bool $dryRun): string
    {
        $codusuario = (int) ($apiHotel['codusuario'] ?? 0);
        $nombre = trim((string) ($apiHotel['nombre'] ?? ''));

        if ($codusuario === 0 || $nombre === '') {
            return 'skipped';
        }

        $usuario = Usuario::withoutGlobalScopes()->find($codusuario);

        if (! $usuario) {
            $this->components->twoColumnDetail("  ⚠ Usuario #{$codusuario} no encontrado", $nombre);

            return 'skipped';
        }

        $direccion = Hotel::withoutGlobalScopes()->where('usuario_id', $codusuario)->first();

        $syncData = [
            'name' => $nombre,
            'title' => $nombre,
        ];

        if ($direccion) {
            $hasChanges = $direccion->name !== $nombre || $direccion->title !== $nombre;

            if (! $hasChanges) {
                return 'skipped';
            }

            if ($dryRun) {
                $this->components->twoColumnDetail("  [actualizar] #{$codusuario}", $nombre);

                return 'updated';
            }

            $direccion->update($syncData);
            $this->components->twoColumnDetail("  ✓ Actualizado #{$codusuario}", $nombre);

            return 'updated';
        }

        if ($dryRun) {
            $this->components->twoColumnDetail("  [crear] #{$codusuario}", $nombre);

            return 'created';
        }

        Hotel::withoutGlobalScopes()->create(array_merge($syncData, [
            'usuario_id' => $codusuario,
            'lat' => 0,
            'lng' => 0,
        ]));

        $this->components->twoColumnDetail("  ✓ Creado #{$codusuario}", $nombre);

        return 'created';
    }
}
