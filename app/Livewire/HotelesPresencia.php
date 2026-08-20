<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;
use Livewire\Component;

class HotelesPresencia extends Component
{
    /** @var array<string, array{nombre: string, solicitudes: int, ultima: string, estado: string, municipio: string}> */
    public array $hoteles = [];

    public int $totalActivos = 0;

    public int $totalRecientes = 0;

    public int $totalInactivos = 0;

    public string $lastUpdated = '';

    public bool $loading = true;

    private const API_URL = 'https://www.taxisnorteysur.com/services/servicios.php';

    private const HOTELES_URL = 'https://www.taxisnorteysur.com/services/usuarios.php';

    public function mount(): void
    {
        $this->loadPresencia();
    }

    public function loadPresencia(): void
    {
        $this->loading = true;

        $allHotels = $this->fetchAllHotels();
        $recentActivity = $this->fetchRecentActivity();

        $now = now();
        $hoteles = [];

        foreach ($allHotels as $hotel) {
            $id = (string) ($hotel['codusuario'] ?? '');
            $nombre = trim((string) ($hotel['nombreUsuario'] ?? ''));

            if ($id === '' || $nombre === '') {
                continue;
            }

            $activity = $recentActivity[$id] ?? null;

            if ($activity) {
                $ultima = $activity['ultima'];
                $minutesAgo = $now->diffInMinutes(\Carbon\Carbon::parse($ultima));

                if ($minutesAgo <= 30) {
                    $estado = 'activo';
                } elseif ($minutesAgo <= 120) {
                    $estado = 'reciente';
                } else {
                    $estado = 'inactivo';
                }
            } else {
                $estado = 'inactivo';
                $ultima = '';
            }

            $hoteles[$id] = [
                'nombre' => $nombre,
                'solicitudes' => $activity['count'] ?? 0,
                'ultima' => $ultima,
                'estado' => $estado,
                'municipio' => $activity['municipio'] ?? '',
            ];
        }

        usort($hoteles, function (array $a, array $b): int {
            $order = ['activo' => 0, 'reciente' => 1, 'inactivo' => 2];

            $estadoCompare = ($order[$a['estado']] ?? 3) <=> ($order[$b['estado']] ?? 3);
            if ($estadoCompare !== 0) {
                return $estadoCompare;
            }

            return $b['solicitudes'] <=> $a['solicitudes'];
        });

        $this->hoteles = $hoteles;
        $this->totalActivos = count(array_filter($hoteles, fn (array $h): bool => $h['estado'] === 'activo'));
        $this->totalRecientes = count(array_filter($hoteles, fn (array $h): bool => $h['estado'] === 'reciente'));
        $this->totalInactivos = count(array_filter($hoteles, fn (array $h): bool => $h['estado'] === 'inactivo'));
        $this->lastUpdated = $now->format('H:i:s');
        $this->loading = false;
    }

    /**
     * @return array<int, array{codusuario: int, nombreUsuario: string}>
     */
    private function fetchAllHotels(): array
    {
        $responses = [];

        foreach ([1, 2, 5] as $municipio) {
            $response = Http::timeout(10)->get(self::HOTELES_URL, [
                'type' => 'listado',
                'codusuario' => $municipio,
            ]);

            if ($response->successful()) {
                $responses = array_merge($responses, $response->json() ?? []);
            }
        }

        return $responses;
    }

    /**
     * @return array<string, array{count: int, ultima: string, municipio: string}>
     */
    private function fetchRecentActivity(): array
    {
        $response = Http::timeout(10)->get(self::API_URL, [
            'type' => 'read',
            'codestado' => 'undefined',
            'reservas' => 0,
            'options' => json_encode([
                'action' => 'read',
                'take' => 500,
                'skip' => 0,
                'page' => 1,
                'pageSize' => 500,
            ]),
        ]);

        if (! $response->successful()) {
            return [];
        }

        $servicios = $response->json('data') ?? [];
        $activity = [];

        foreach ($servicios as $servicio) {
            $hotelId = (string) ($servicio['codusuario'] ?? '');
            $fecha = (string) ($servicio['fecha_servicio'] ?? '');

            if ($hotelId === '' || $fecha === '') {
                continue;
            }

            if (! isset($activity[$hotelId])) {
                $activity[$hotelId] = [
                    'count' => 0,
                    'ultima' => $fecha,
                    'municipio' => (string) ($servicio['nombreMunicipio'] ?? ''),
                ];
            }

            $activity[$hotelId]['count']++;

            if ($fecha > $activity[$hotelId]['ultima']) {
                $activity[$hotelId]['ultima'] = $fecha;
            }
        }

        return $activity;
    }

    #[On('solicitud-taxi-recibida')]
    public function onSolicitudRecibida(): void
    {
        $this->loadPresencia();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.hoteles-presencia');
    }
}
