<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Taxi\EstadosServicio;
use App\Models\Taxi\Servicio;
use App\Models\Taxi\UsuarioDireccion;
use App\Models\Taxi\Usuario;
use Illuminate\Support\Carbon;

class ServicioQueryService
{
    /**
     * Lista servicios paginados con filtro de estado, hotel y fechas.
     * Reemplaza: Http::get(API_URL, type=read)
     *
     * @return array{data: array, total_pend: int, total_total: int, total_pages: int}
     */
    public function listar(string $estadoFilter = '', int $page = 1, int $pageSize = 20, ?int $hotelFilter = null, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $query = Servicio::query()
            ->with(['estado:id,nombre', 'tipotaxi:id,nombre', 'municipio:id,nombre', 'usuario:id,nombre,email,tel_fijo,tipo_id'])
            ->orderByDesc('id');

        if ($estadoFilter !== '' && $estadoFilter !== 'undefined') {
            $query->where('estado_id', (int) $estadoFilter);
        }

        if ($hotelFilter && $hotelFilter > 0) {
            $query->where('usuario_id', $hotelFilter);
        }

        // Filtro por fecha desde
        if ($fechaDesde) {
            try {
                $fechaDesdeCarbon = Carbon::createFromFormat('Y-m-d', $fechaDesde)->startOfDay();
                $query->where('fecha_servicio', '>=', $fechaDesdeCarbon);
            } catch (\Exception $e) {
                // Ignorar fecha inválida
            }
        }

        // Filtro por fecha hasta
        if ($fechaHasta) {
            try {
                $fechaHastaCarbon = Carbon::createFromFormat('Y-m-d', $fechaHasta)->endOfDay();
                $query->where('fecha_servicio', '<=', $fechaHastaCarbon);
            } catch (\Exception $e) {
                // Ignorar fecha inválida
            }
        }

        $total = $query->count();
        $data = $query->skip(($page - 1) * $pageSize)->take($pageSize)->get();

        $totalPendQuery = Servicio::where('estado_id', 1);
        if ($hotelFilter && $hotelFilter > 0) {
            $totalPendQuery->where('usuario_id', $hotelFilter);
        }
        if ($fechaDesde) {
            try {
                $fechaDesdeCarbon = Carbon::createFromFormat('Y-m-d', $fechaDesde)->startOfDay();
                $totalPendQuery->where('fecha_servicio', '>=', $fechaDesdeCarbon);
            } catch (\Exception $e) {
                // Ignorar fecha inválida
            }
        }
        if ($fechaHasta) {
            try {
                $fechaHastaCarbon = Carbon::createFromFormat('Y-m-d', $fechaHasta)->endOfDay();
                $totalPendQuery->where('fecha_servicio', '<=', $fechaHastaCarbon);
            } catch (\Exception $e) {
                // Ignorar fecha inválida
            }
        }
        $totalPend = $totalPendQuery->count();

        $mapped = $data->map(fn (Servicio $s): array => $this->mapServicio($s))->values()->all();

        return [
            'data' => $mapped,
            'total_pend' => $totalPend,
            'total_total' => $total,
            'total_pages' => (int) ceil($total / $pageSize),
        ];
    }

    /**
     * Stats por municipio para hoy y este mes.
     * Reemplaza: Http::get(API_URL) + procesado en PHP del StatsBar.
     *
     * @return array{stats: array<string, array{hoy: int, mes: int}>, totalHoy: int, totalMes: int}
     */
    public function statsPorMunicipio(): array
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();

        $serviciosHoy = Servicio::query()
            ->join('municipios', 'taxis_servicios.municipio_id', '=', 'municipios.id')
            ->where('fecha_servicio', '>=', $hoy)
            ->selectRaw('municipios.nombre as municipio, count(*) as total')
            ->groupBy('municipios.nombre')
            ->pluck('total', 'municipio');

        $serviciosMes = Servicio::query()
            ->join('municipios', 'taxis_servicios.municipio_id', '=', 'municipios.id')
            ->where('fecha_servicio', '>=', $inicioMes)
            ->selectRaw('municipios.nombre as municipio, count(*) as total')
            ->groupBy('municipios.nombre')
            ->pluck('total', 'municipio');

        $municipiosKey = ['Tías', 'Yaiza', 'Teguise'];
        $stats = [];

        foreach ($municipiosKey as $mun) {
            $stats[$mun] = [
                'hoy' => $serviciosHoy->get($mun, 0),
                'mes' => $serviciosMes->get($mun, 0),
            ];
        }

        return [
            'stats' => $stats,
            'totalHoy' => $serviciosHoy->sum(),
            'totalMes' => $serviciosMes->sum(),
        ];
    }

    /**
     * Datos de markers para el mapa agrupados por hotel.
     * Reemplaza: Http::get(API_URL, pageSize=300) + procesado en PHP del MapaLive.
     *
     * @return array{markers: array, totalSolicitudes: int}
     */
    public function markersParaMapa(): array
    {
        $servicios = Servicio::query()
            ->with(['usuario:id,nombre', 'municipio:id,nombre', 'estado:id,nombre', 'tipotaxi:id,nombre'])
            ->orderByDesc('fecha_servicio')
            ->limit(500)
            ->get();

        $hotelActivity = [];

        foreach ($servicios as $s) {
            $hotelId = $s->usuario_id;
            if (! $hotelId) {
                continue;
            }

            if (! isset($hotelActivity[$hotelId])) {
                $hotelActivity[$hotelId] = [
                    'nombre' => $s->usuario?->nombre ?? '',
                    'municipio' => $s->municipio?->nombre ?? '',
                    'count' => 0,
                    'pendientes' => 0,
                    'tramitados' => 0,
                    'ultima' => '',
                    'solicitudes' => [],
                ];
            }

            $hotelActivity[$hotelId]['count']++;
            $estado = $s->estado?->nombre ?? '';

            if ($estado === 'SOLICITADO') {
                $hotelActivity[$hotelId]['pendientes']++;
            } elseif ($estado === 'TRAMITADO') {
                $hotelActivity[$hotelId]['tramitados']++;
            }

            $fecha = $s->fecha_servicio?->format('Y-m-d H:i') ?? '';
            if ($fecha > $hotelActivity[$hotelId]['ultima']) {
                $hotelActivity[$hotelId]['ultima'] = $fecha;
            }

            if (count($hotelActivity[$hotelId]['solicitudes']) < 5) {
                $hotelActivity[$hotelId]['solicitudes'][] = [
                    'nombre' => $s->nombre ?? '',
                    'hab' => (string) ($s->habitacion ?? ''),
                    'pax' => $s->personas ?? 0,
                    'tipo' => $s->tipotaxi?->nombre ?? '',
                    'estado' => $estado,
                    'fecha' => $fecha,
                ];
            }
        }

        $hotelIds = array_keys($hotelActivity);

        $direcciones = UsuarioDireccion::query()
            ->whereIn('usuario_id', $hotelIds)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('lat', '!=', '0')
            ->where('lng', '!=', '0')
            ->get()
            ->keyBy('usuario_id');

        $markers = [];
        foreach ($hotelActivity as $hotelId => $activity) {
            $direccion = $direcciones->get($hotelId);
            if (! $direccion) {
                continue;
            }

            $lat = (float) $direccion->lat;
            $lng = (float) $direccion->lng;

            if ($lat === 0.0 && $lng === 0.0) {
                continue;
            }

            $markers[] = [
                'id' => $hotelId,
                'lat' => $lat,
                'lng' => $lng,
                'nombre' => $activity['nombre'],
                'municipio' => $activity['municipio'],
                'count' => $activity['count'],
                'pendientes' => $activity['pendientes'],
                'tramitados' => $activity['tramitados'],
                'ultima' => $activity['ultima'],
                'solicitudes' => $activity['solicitudes'],
            ];
        }

        return [
            'markers' => $markers,
            'totalSolicitudes' => array_sum(array_column($markers, 'count')),
        ];
    }

    /**
     * Servicios para sidebar con filtro de estado.
     * Reemplaza: Http::get(API_URL, type=read, codestado=X)
     *
     * @return array<int, array<string, mixed>>
     */
    public function paraSidebar(string $estadoFilter = '', int $limit = 30): array
    {
        if ($estadoFilter !== '') {
            return Servicio::query()
                ->with(['estado:id,nombre', 'tipotaxi:id,nombre', 'municipio:id,nombre', 'usuario:id,nombre,email,tel_fijo,tipo_id'])
                ->where('estado_id', (int) $estadoFilter)
                ->orderByDesc('fecha_servicio')
                ->limit($limit)
                ->get()
                ->map(fn (Servicio $s): array => $this->mapServicio($s))
                ->values()
                ->all();
        }

        $merged = collect();
        foreach ([1, 2, 3] as $cod) {
            $merged = $merged->concat(
                Servicio::query()
                    ->with(['estado:id,nombre', 'tipotaxi:id,nombre', 'municipio:id,nombre', 'usuario:id,nombre,email,tel_fijo,tipo_id'])
                    ->where('estado_id', $cod)
                    ->orderByDesc('fecha_servicio')
                    ->limit(12)
                    ->get()
            );
        }

        return $merged
            ->sortByDesc('fecha_servicio')
            ->take($limit)
            ->map(fn (Servicio $s): array => $this->mapServicio($s))
            ->values()
            ->all();
    }

    /**
     * Mapea un Servicio Eloquent al formato array que esperan las vistas Blade.
     */
    private function mapServicio(Servicio $s): array
    {
        return [
            'codservicio' => $s->id,
            'nombre' => $s->nombre,
            'codusuario' => $s->usuario_id,
            'codestado' => $s->estado_id,
            'codoperador' => $s->operador_id,
            'personas' => $s->personas,
            'fecha_servicio' => $s->fecha_servicio?->format('Y-m-d H:i'),
            'fecha_terminado' => $s->fecha_terminado?->format('Y-m-d H:i'),
            'codtipotaxi' => $s->tipotaxi_id,
            'codmunicipio' => $s->municipio_id,
            'habitacion' => $s->habitacion,
            'tarjeta_credito' => $s->tarjeta_credito,
            'respuesta' => $s->respuesta,
            'nombreUsuario' => $s->usuario?->nombre ?? '',
            'nombreMunicipio' => $s->municipio?->nombre ?? '',
            'nombreTipo' => $s->tipotaxi?->nombre ?? '',
            'nombreEstado' => $s->estado?->nombre ?? '',
            'nombre_cliente' => $s->nombre_cliente,
            'tfno_cliente' => $s->tfno_cliente,
            'booking_id' => null,
            'status' => null,
            'booking_callsign' => null,
        ];
    }

    /**
     * Obtiene lista de hoteles disponibles para filtro.
     *
     * @return array<int, array{id: int, nombre: string}>
     */
    public function getHotelesDisponibles(): array
    {
        return Usuario::query()
            ->where('tipo_id', 2) // Hoteles
            ->where('estado_id', 1)   // Activos
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (Usuario $u): array => [
                'id' => $u->id,
                'nombre' => $u->nombre,
            ])
            ->values()
            ->all();
    }
}
