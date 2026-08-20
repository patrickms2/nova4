<?php

namespace App\Services\MCP;

use App\Models\Taxi\Booking;
use App\Models\Taxi\Conductor;
use App\Models\Taxi\Hotel;
use App\Models\Taxi\ReceptionistCommission;
use App\Models\Taxi\ReceptionistPayout;
use App\Models\Taxi\Servicio;
use App\Models\Taxi\Usuario;
use Illuminate\Support\Facades\Http;

class TaxilanzMCPServer
{
    private $aurigaConfig;

    public function __construct()
    {
        $this->aurigaConfig = [
            'endpoint' => config('services.auriga.endpoint'),
            'api_key' => config('services.auriga.api_key'),
        ];
    }

    /**
     * Get all available MCP tools
     */
    public function getTools(): array
    {
        return [
            // ===== GESTIÓN DE HOTELES =====
            [
                'name' => 'hotel_list',
                'description' => 'List all connected hotels with status and activity',
                'category' => 'location',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'enum' => ['active', 'inactive', 'all'],
                            'default' => 'active',
                        ],
                        'zone' => [
                            'type' => 'string',
                            'enum' => ['tias', 'yaiza', 'arrecife', 'playa_blanca', 'all'],
                            'default' => 'all',
                        ],
                        'page' => ['type' => 'integer', 'default' => 1],
                        'per_page' => ['type' => 'integer', 'default' => 50],
                    ],
                ],
            ],
            [
                'name' => 'hotel_get',
                'description' => 'Get specific hotel details and status',
                'category' => 'location',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'hotel_status_update',
                'description' => 'Update hotel connection status',
                'category' => 'location',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['active', 'inactive'],
                        ],
                    ],
                    'required' => ['id', 'status'],
                ],
            ],
            [
                'name' => 'hotel_stats_get',
                'description' => 'Get hotel statistics (services, reservations)',
                'category' => 'analytics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'hotel_id' => ['type' => 'integer'],
                        'period' => [
                            'type' => 'string',
                            'enum' => ['today', 'week', 'month', 'year'],
                            'default' => 'today',
                        ],
                    ],
                    'required' => ['hotel_id'],
                ],
            ],

            // ===== ESTADÍSTICAS POR ZONA =====
            [
                'name' => 'zone_stats_get',
                'description' => 'Get taxi statistics by zone (Tias, Yaiza, etc.)',
                'category' => 'analytics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'zone' => [
                            'type' => 'string',
                            'enum' => ['tias', 'yaiza', 'arrecife', 'playa_blanca', 'all'],
                            'default' => 'all',
                        ],
                        'period' => [
                            'type' => 'string',
                            'enum' => ['today', 'month'],
                            'default' => 'today',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'zone_total_get',
                'description' => 'Get total taxi requests by zone',
                'category' => 'analytics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => [
                            'type' => 'string',
                            'enum' => ['today', 'month'],
                            'default' => 'today',
                        ],
                    ],
                ],
            ],

            // ===== RESERVAS DE TAXI =====
            [
                'name' => 'booking_create',
                'description' => 'Create taxi booking with real-time Auriga API',
                'category' => 'booking',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_phone' => ['type' => 'string'],
                        'customer_name' => ['type' => 'string'],
                        'pickup_location' => ['type' => 'string'],
                        'dropoff_location' => ['type' => 'string'],
                        'pickup_hotel_id' => ['type' => 'integer'],
                        'date' => ['type' => 'string', 'format' => 'date'],
                        'time' => ['type' => 'string'],
                        'passengers' => ['type' => 'integer', 'default' => 1],
                        'payment_method' => [
                            'type' => 'string',
                            'enum' => ['cash', 'card', 'revolut', 'bizum'],
                        ],
                        'use_reward_points' => ['type' => 'boolean', 'default' => false],
                        'receptionist_id' => ['type' => 'integer'],
                    ],
                    'required' => ['customer_phone', 'pickup_location', 'dropoff_location', 'date', 'time'],
                ],
            ],
            [
                'name' => 'booking_get',
                'description' => 'Get specific taxi booking',
                'category' => 'booking',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'booking_list',
                'description' => 'List taxi bookings with filters',
                'category' => 'booking',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'hotel_id' => ['type' => 'integer'],
                        'date' => ['type' => 'string', 'format' => 'date'],
                        'status' => ['type' => 'string'],
                        'customer_phone' => ['type' => 'string'],
                        'zone' => ['type' => 'string'],
                        'page' => ['type' => 'integer', 'default' => 1],
                        'per_page' => ['type' => 'integer', 'default' => 20],
                    ],
                ],
            ],
            [
                'name' => 'booking_cancel',
                'description' => 'Cancel taxi booking',
                'category' => 'booking',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                    'required' => ['id'],
                ],
            ],

            // ===== SERVICIOS RECIENTES =====
            [
                'name' => 'service_list_latest',
                'description' => 'Get latest taxi services',
                'category' => 'analytics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'default' => 10],
                        'zone' => ['type' => 'string'],
                    ],
                ],
            ],

            // ===== CONDUCTORES =====
            [
                'name' => 'driver_get_available',
                'description' => 'Get available drivers',
                'category' => 'staff',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => ['type' => 'string'],
                        'date' => ['type' => 'string', 'format' => 'date'],
                        'time' => ['type' => 'string'],
                    ],
                    'required' => ['location', 'date', 'time'],
                ],
            ],
            [
                'name' => 'driver_list',
                'description' => 'List all drivers with status',
                'category' => 'staff',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'enum' => ['available', 'busy', 'offline', 'all'],
                            'default' => 'all',
                        ],
                        'zone' => ['type' => 'string'],
                        'page' => ['type' => 'integer', 'default' => 1],
                        'per_page' => ['type' => 'integer', 'default' => 20],
                    ],
                ],
            ],

            // ===== MAPA Y UBICACIONES =====
            [
                'name' => 'location_map_markers',
                'description' => 'Get map markers for hotels and active services',
                'category' => 'location',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'zone' => ['type' => 'string'],
                        'show_hotels' => ['type' => 'boolean', 'default' => true],
                        'show_active_services' => ['type' => 'boolean', 'default' => true],
                    ],
                ],
            ],

            [
                'name' => 'receptionist_list',
                'description' => 'List receptionists by hotel',
                'category' => 'staff',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'hotel_id' => ['type' => 'integer'],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['active', 'inactive', 'all'],
                            'default' => 'active',
                        ],
                        'page' => ['type' => 'integer', 'default' => 1],
                        'per_page' => ['type' => 'integer', 'default' => 20],
                    ],
                ],
            ],
            [
                'name' => 'receptionist_commission_realtime',
                'description' => 'Get receptionist real-time commission data',
                'category' => 'commission',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'receptionist_id' => ['type' => 'integer'],
                    ],
                    'required' => ['receptionist_id'],
                ],
            ],
            [
                'name' => 'receptionist_commission_award',
                'description' => 'Award commission points to a receptionist',
                'category' => 'commission',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'receptionist_id' => ['type' => 'integer'],
                        'hotel_id' => ['type' => 'integer'],
                        'service_id' => ['type' => 'integer'],
                        'tourist_phone' => ['type' => 'string'],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['app_download', 'chatbot_purchase', 'taxi_booking'],
                        ],
                        'purchase_amount' => ['type' => 'number'],
                        'points' => ['type' => 'integer'],
                        'amount' => ['type' => 'number'],
                    ],
                    'required' => ['receptionist_id', 'type'],
                ],
            ],
            [
                'name' => 'receptionist_payout_request',
                'description' => 'Request receptionist instant payout',
                'category' => 'payment',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'receptionist_id' => ['type' => 'integer'],
                        'amount' => ['type' => 'number'],
                        'method' => [
                            'type' => 'string',
                            'enum' => ['revolut', 'manual'],
                            'default' => 'revolut',
                        ],
                    ],
                    'required' => ['receptionist_id'],
                ],
            ],

            // ===== ESTIMACIONES =====
            [
                'name' => 'price_estimate',
                'description' => 'Get price estimate for route',
                'category' => 'analytics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'pickup_location' => ['type' => 'string'],
                        'dropoff_location' => ['type' => 'string'],
                        'distance_km' => ['type' => 'number'],
                    ],
                    'required' => ['pickup_location', 'dropoff_location'],
                ],
            ],
        ];
    }

    public function getHotels(): array
    {

        $hoteles = Usuario::with(['servicios'])->where('usuarios.tipo_id', '=', '2')->limit(10)->get()->toArray();

        return $hoteles;

    }

    public function getServicios(): array
    {

        $servicios = Servicio::with(['usuario'])->limit(10)->get()->toArray();

        return $servicios;

    }

    /**
     * Execute a specific MCP tool
     */
    public function executeTool(string $toolName, array $arguments): array
    {
        try {
            switch ($toolName) {
                // ===== GESTIÓN DE HOTELES =====
                case 'hotel_list':
                    return $this->hotelList($arguments);
                case 'hotel_get':
                    return $this->hotelGet($arguments);
                case 'hotel_status_update':
                    return $this->hotelStatusUpdate($arguments);
                case 'hotel_stats_get':
                    return $this->hotelStatsGet($arguments);

                    // ===== ESTADÍSTICAS POR ZONA =====
                case 'zone_stats_get':
                    return $this->zoneStatsGet($arguments);
                case 'zone_total_get':
                    return $this->zoneTotalGet($arguments);

                    // ===== RESERVAS DE TAXI =====
                case 'booking_create':
                    return $this->bookingCreate($arguments);
                case 'booking_get':
                    return $this->bookingGet($arguments);
                case 'booking_list':
                    return $this->bookingList($arguments);
                case 'booking_cancel':
                    return $this->bookingCancel($arguments);

                    // ===== SERVICIOS RECIENTES =====
                case 'service_list_latest':
                    return $this->serviceListLatest($arguments);

                    // ===== CONDUCTORES =====
                case 'driver_get_available':
                    return $this->driverGetAvailable($arguments);
                case 'driver_list':
                    return $this->driverList($arguments);

                    // ===== MAPA Y UBICACIONES =====
                case 'location_map_markers':
                    return $this->locationMapMarkers($arguments);

                case 'receptionist_list':
                    return $this->receptionistList($arguments);
                case 'receptionist_commission_realtime':
                    return $this->receptionistCommissionRealtime($arguments);
                case 'receptionist_commission_award':
                    return $this->receptionistCommissionAward($arguments);
                case 'receptionist_payout_request':
                    return $this->receptionistPayoutRequest($arguments);

                    // ===== ESTIMACIONES =====
                case 'price_estimate':
                    return $this->priceEstimate($arguments);

                default:
                    return [
                        'success' => false,
                        'error' => [
                            'code' => 'TOOL_NOT_FOUND',
                            'message' => "Tool '{$toolName}' not found",
                        ],
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'EXECUTION_ERROR',
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    // ===== IMPLEMENTACIÓN DE HERRAMIENTAS DE HOTELES =====

    private function hotelList(array $args): array
    {
        $query = Hotel::query();

        // Filtrar por estado
        if (isset($args['status']) && $args['status'] !== 'all') {
            $query->whereHas('usuario', function ($q) use ($args) {
                $q->where('estado_id', $args['status'] === 'active' ? 1 : 0);
            });
        }

        // Filtrar por zona
        if (isset($args['zone']) && $args['zone'] !== 'all') {
            $zoneMapping = [
                'tias' => 1,
                'yaiza' => 2,
                'arrecife' => 3,
                'playa_blanca' => 4,
            ];
            if (isset($zoneMapping[$args['zone']])) {
                $query->whereHas('usuario', function ($q) use ($zoneMapping, $args) {
                    $q->where('municipio_id', $zoneMapping[$args['zone']]);
                });
            }
        }

        // Paginación
        $page = $args['page'] ?? 1;
        $perPage = $args['per_page'] ?? 50;

        $hotels = $query->with(['usuario'])
            ->orderBy('title')
            ->paginate($perPage, ['*'], 'page', $page);

        $hotelData = $hotels->map(function ($hotel) {
            return [
                'id' => $hotel->id,
                'name' => $hotel->title,
                'zone' => $hotel->usuario->municipio_id ?? null,
                'status' => $hotel->status ? 'active' : 'inactive',
                'location' => [
                    'lat' => (float) $hotel->lat,
                    'lng' => (float) $hotel->lng,
                    'address' => $hotel->address,
                ],
                'phone' => $hotel->phone,
                'services_today' => $this->getHotelServicesCount($hotel->id, 'today'),
                'services_month' => $this->getHotelServicesCount($hotel->id, 'month'),
                'reservations_today' => $this->getHotelReservationsCount($hotel->id, 'today'),
                'reservations_month' => $this->getHotelReservationsCount($hotel->id, 'month'),
            ];
        });

        return [
            'success' => true,
            'data' => [
                'hotels' => $hotelData->toArray(),
                'summary' => [
                    'total_active' => Hotel::whereHas('usuario', fn ($q) => $q->where('estado_id', 1))->count(),
                    'total_inactive' => Hotel::whereHas('usuario', fn ($q) => $q->where('estado_id', 0))->count(),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ],
            ],
            'meta' => [
                'total' => $hotels->total(),
                'page' => $hotels->currentPage(),
                'per_page' => $hotels->perPage(),
                'has_more' => $hotels->hasMorePages(),
            ],
        ];
    }

    private function hotelGet(array $args): array
    {
        $hotel = Hotel::with(['usuario'])->findOrFail($args['id']);

        return [
            'success' => true,
            'data' => [
                'id' => $hotel->id,
                'name' => $hotel->title,
                'zone' => $hotel->usuario->municipio_id ?? null,
                'status' => $hotel->status ? 'active' : 'inactive',
                'location' => [
                    'lat' => (float) $hotel->lat,
                    'lng' => (float) $hotel->lng,
                    'address' => $hotel->address,
                    'formatted_address' => $hotel->formatted_address,
                ],
                'contact' => [
                    'phone' => $hotel->phone,
                    'website' => $hotel->website,
                ],
                'services_today' => $this->getHotelServicesCount($hotel->id, 'today'),
                'services_month' => $this->getHotelServicesCount($hotel->id, 'month'),
                'reservations_today' => $this->getHotelReservationsCount($hotel->id, 'today'),
                'reservations_month' => $this->getHotelReservationsCount($hotel->id, 'month'),
            ],
        ];
    }

    private function hotelStatusUpdate(array $args): array
    {
        $hotel = Hotel::findOrFail($args['id']);
        $hotel->usuario->estado_id = $args['status'] === 'active' ? 1 : 0;
        $hotel->usuario->save();

        return [
            'success' => true,
            'data' => [
                'id' => $hotel->id,
                'status' => $args['status'],
            ],
        ];
    }

    private function hotelStatsGet(array $args): array
    {
        $hotel = Hotel::findOrFail($args['hotel_id']);
        $period = $args['period'] ?? 'today';

        return [
            'success' => true,
            'data' => [
                'hotel_id' => $hotel->id,
                'hotel_name' => $hotel->title,
                'period' => $period,
                'services_count' => $this->getHotelServicesCount($hotel->id, $period),
                'reservations_count' => $this->getHotelReservationsCount($hotel->id, $period),
                'total_revenue' => $this->getHotelRevenue($hotel->id, $period),
            ],
        ];
    }

    // ===== IMPLEMENTACIÓN DE ESTADÍSTICAS POR ZONA =====

    private function zoneStatsGet(array $args): array
    {
        $zone = $args['zone'] ?? 'all';
        $period = $args['period'] ?? 'today';

        $zoneMapping = [
            'tias' => 1,
            'yaiza' => 2,
            'arrecife' => 3,
            'playa_blanca' => 4,
        ];

        $query = Servicio::query();

        if ($zone !== 'all' && isset($zoneMapping[$zone])) {
            $query->where('municipio_id', $zoneMapping[$zone]);
        }

        if ($period === 'today') {
            $query->whereDate('fecha_servicio', today());
        } elseif ($period === 'month') {
            $query->whereMonth('fecha_servicio', now()->month);
        }

        $services = $query->get();

        $stats = [
            'tias' => ['today' => 0, 'month' => 0],
            'yaiza' => ['today' => 0, 'month' => 0],
            'arrecife' => ['today' => 0, 'month' => 0],
            'playa_blanca' => ['today' => 0, 'month' => 0],
            'others' => ['today' => 0, 'month' => 0],
        ];

        foreach ($services as $service) {
            $zoneKey = array_search($service->municipio_id, $zoneMapping) ?: 'others';
            if ($period === 'today') {
                $stats[$zoneKey]['today']++;
            } else {
                $stats[$zoneKey]['month']++;
            }
        }

        return [
            'success' => true,
            'data' => $stats,
        ];
    }

    private function zoneTotalGet(array $args): array
    {
        $period = $args['period'] ?? 'today';

        $query = Servicio::query();

        if ($period === 'today') {
            $query->whereDate('fecha_servicio', today());
        } elseif ($period === 'month') {
            $query->whereMonth('fecha_servicio', now()->month);
        }

        $total = $query->count();

        return [
            'success' => true,
            'data' => [
                'period' => $period,
                'total' => $total,
            ],
        ];
    }

    // ===== IMPLEMENTACIÓN DE RESERVAS DE TAXI =====

    private function bookingCreate(array $args): array
    {
        // 1. Llamar a API Auriga (simulado por ahora)
        $aurigaResponse = $this->callAurigaAPI([
            'pickup' => $args['pickup_location'],
            'dropoff' => $args['dropoff_location'],
            'date' => $args['date'],
            'time' => $args['time'],
            'passengers' => $args['passengers'] ?? 1,
        ]);

        // 2. Crear servicio en base de datos
        $servicio = Servicio::create([
            'nombre' => 'Taxi booking via MCP',
            'usuario_id' => $args['pickup_hotel_id'] ?? null,
            'fecha_servicio' => $args['date'].' '.$args['time'],
            'personas' => $args['passengers'] ?? 1,
            'observaciones' => $args['pickup_location'].' -> '.$args['dropoff_location'],
            'estado_id' => 1, // Pendiente
            'extras' => $args['customer_phone'],
        ]);

        // 3. Crear booking si existe bookingId de Auriga
        if (isset($aurigaResponse['booking_id'])) {
            Booking::create([
                'servicio_id' => $servicio->id,
                'booking_id' => $aurigaResponse['booking_id'],
            ]);
        }

        return [
            'success' => true,
            'data' => [
                'booking_id' => $servicio->id,
                'auriga_booking_id' => $aurigaResponse['booking_id'] ?? null,
                'status' => 'pending',
                'estimated_price' => $aurigaResponse['estimated_price'] ?? null,
                'eta' => $aurigaResponse['eta'] ?? null,
            ],
        ];
    }

    private function bookingGet(array $args): array
    {
        $servicio = Servicio::with(['booking', 'usuario', 'conductor', 'taxi'])
            ->findOrFail($args['id']);

        return [
            'success' => true,
            'data' => [
                'id' => $servicio->id,
                'booking_id' => $servicio->booking->booking_id ?? null,
                'customer_phone' => $servicio->extras,
                'pickup_location' => $servicio->observaciones,
                'date' => $servicio->fecha_servicio->format('Y-m-d'),
                'time' => $servicio->fecha_servicio->format('H:i'),
                'passengers' => $servicio->personas,
                'status' => $servicio->nombre_estado,
                'driver' => $servicio->conductor ? $servicio->conductor->nombre : null,
                'taxi' => $servicio->taxi ? $servicio->taxi->matricula : null,
            ],
        ];
    }

    private function bookingList(array $args): array
    {
        $query = Servicio::query();

        if (isset($args['hotel_id'])) {
            $query->where('usuario_id', $args['hotel_id']);
        }

        if (isset($args['date'])) {
            $query->whereDate('fecha_servicio', $args['date']);
        }

        if (isset($args['status'])) {
            $query->where('estado_id', $args['status']);
        }

        if (isset($args['customer_phone'])) {
            $query->where('extras', 'like', '%'.$args['customer_phone'].'%');
        }

        $page = $args['page'] ?? 1;
        $perPage = $args['per_page'] ?? 20;

        $bookings = $query->with(['booking', 'usuario', 'conductor'])
            ->orderBy('fecha_servicio', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'data' => $bookings->items(),
            'meta' => [
                'total' => $bookings->total(),
                'page' => $bookings->currentPage(),
                'per_page' => $bookings->perPage(),
                'has_more' => $bookings->hasMorePages(),
            ],
        ];
    }

    private function bookingCancel(array $args): array
    {
        $servicio = Servicio::findOrFail($args['id']);
        $servicio->estado_id = 4; // Cancelado
        $servicio->save();

        return [
            'success' => true,
            'data' => [
                'id' => $servicio->id,
                'status' => 'cancelled',
            ],
        ];
    }

    // ===== IMPLEMENTACIÓN DE SERVICIOS RECIENTES =====

    private function serviceListLatest(array $args): array
    {
        $limit = $args['limit'] ?? 10;

        $services = Servicio::with(['usuario', 'conductor'])
            ->orderBy('fecha_servicio', 'desc')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'data' => $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'hotel_name' => $service->usuario ? $service->usuario->nombre : 'N/A',
                    'date' => $service->fecha_servicio->format('Y-m-d H:i'),
                    'status' => $service->nombre_estado,
                    'driver' => $service->conductor ? $service->conductor->nombre : null,
                ];
            })->toArray(),
        ];
    }

    // ===== IMPLEMENTACIÓN DE CONDUCTORES =====

    private function driverGetAvailable(array $args): array
    {
        $drivers = Conductor::activos()
            ->where('estado_id', 1)
            ->limit(10)
            ->get();

        return [
            'success' => true,
            'data' => $drivers->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->nombre,
                    'phone' => $driver->tel_fijo,
                    'license' => $driver->licencia,
                    'location' => [
                        'lat' => (float) $driver->lat,
                        'lng' => (float) $driver->lng,
                    ],
                ];
            })->toArray(),
        ];
    }

    private function driverList(array $args): array
    {
        $query = Conductor::query();

        if (isset($args['status']) && $args['status'] !== 'all') {
            $statusMap = [
                'available' => 1,
                'busy' => 2,
                'offline' => 0,
            ];
            if (isset($statusMap[$args['status']])) {
                $query->where('estado_id', $statusMap[$args['status']]);
            }
        }

        $page = $args['page'] ?? 1;
        $perPage = $args['per_page'] ?? 20;

        $drivers = $query->orderBy('nombre')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'data' => $drivers->items(),
            'meta' => [
                'total' => $drivers->total(),
                'page' => $drivers->currentPage(),
                'per_page' => $drivers->perPage(),
                'has_more' => $drivers->hasMorePages(),
            ],
        ];
    }

    // ===== IMPLEMENTACIÓN DE MAPA Y UBICACIONES =====

    private function locationMapMarkers(array $args): array
    {
        $markers = [];

        if ($args['show_hotels'] ?? true) {
            $hotels = Hotel::whereHas('usuario', fn ($q) => $q->where('estado_id', 1))
                ->get();

            foreach ($hotels as $hotel) {
                $markers[] = [
                    'type' => 'hotel',
                    'id' => $hotel->id,
                    'name' => $hotel->title,
                    'location' => [
                        'lat' => (float) $hotel->lat,
                        'lng' => (float) $hotel->lng,
                    ],
                    'status' => 'active',
                ];
            }
        }

        if ($args['show_active_services'] ?? true) {
            $activeServices = Servicio::where('estado_id', 2) // En proceso
                ->whereDate('fecha_servicio', today())
                ->get();

            foreach ($activeServices as $service) {
                if ($service->usuario) {
                    $markers[] = [
                        'type' => 'active_service',
                        'id' => $service->id,
                        'location' => [
                            'lat' => (float) $service->usuario->lat,
                            'lng' => (float) $service->usuario->lng,
                        ],
                        'status' => 'in_progress',
                    ];
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'markers' => $markers,
                'count' => count($markers),
            ],
        ];
    }

    private function receptionistList(array $args): array
    {
        $query = Usuario::query();

        if (isset($args['hotel_id'])) {
            $hotel = Hotel::findOrFail($args['hotel_id']);
            $query->where('departamento_id', $hotel->usuario_id);
        }

        if (isset($args['status']) && $args['status'] !== 'all') {
            $query->where('estado_id', $args['status'] === 'active' ? 1 : 0);
        }

        $page = $args['page'] ?? 1;
        $perPage = $args['per_page'] ?? 20;

        $receptionists = $query
            ->where(function ($query): void {
                $query->where('tipo_id', 2)
                    ->orWhere('departamento_id', '>', 0);
            })
            ->orderBy('nombre')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'data' => collect($receptionists->items())->map(fn (Usuario $receptionist): array => [
                'id' => $receptionist->id,
                'name' => $receptionist->nombre,
                'email' => $receptionist->email,
                'phone' => $receptionist->tel_fijo,
                'hotel_id' => $args['hotel_id'] ?? null,
                'status' => ((int) $receptionist->estado_id) === 1 ? 'active' : 'inactive',
                'commission' => $this->buildReceptionistCommissionSummary($receptionist->id),
            ])->toArray(),
            'meta' => [
                'total' => $receptionists->total(),
                'page' => $receptionists->currentPage(),
                'per_page' => $receptionists->perPage(),
                'has_more' => $receptionists->hasMorePages(),
            ],
        ];
    }

    private function receptionistCommissionRealtime(array $args): array
    {
        $receptionist = Usuario::findOrFail($args['receptionist_id']);

        return [
            'success' => true,
            'data' => [
                'receptionist' => [
                    'id' => $receptionist->id,
                    'name' => $receptionist->nombre,
                    'email' => $receptionist->email,
                    'phone' => $receptionist->tel_fijo,
                ],
                'commission' => $this->buildReceptionistCommissionSummary($receptionist->id),
                'latest_commissions' => ReceptionistCommission::query()
                    ->where('receptionist_id', $receptionist->id)
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(fn (ReceptionistCommission $commission): array => [
                        'id' => $commission->id,
                        'type' => $commission->type,
                        'points' => $commission->points,
                        'amount' => (float) $commission->amount,
                        'currency' => $commission->currency,
                        'status' => $commission->status,
                        'tourist_phone' => $commission->tourist_phone,
                        'created_at' => $commission->created_at?->toDateTimeString(),
                    ])
                    ->toArray(),
            ],
        ];
    }

    private function receptionistCommissionAward(array $args): array
    {
        $receptionist = Usuario::findOrFail($args['receptionist_id']);
        $type = $args['type'];
        $purchaseAmount = (float) ($args['purchase_amount'] ?? 0);
        $amount = isset($args['amount']) ? (float) $args['amount'] : $this->calculateReceptionistCommissionAmount($type, $purchaseAmount);
        $points = isset($args['points']) ? (int) $args['points'] : $this->calculateReceptionistCommissionPoints($amount);

        $commission = ReceptionistCommission::create([
            'receptionist_id' => $receptionist->id,
            'hotel_id' => $args['hotel_id'] ?? null,
            'service_id' => $args['service_id'] ?? null,
            'tourist_phone' => $args['tourist_phone'] ?? null,
            'type' => $type,
            'points' => $points,
            'amount' => $amount,
            'currency' => 'EUR',
            'status' => 'pending',
            'metadata' => [
                'purchase_amount' => $purchaseAmount,
                'source' => 'mcp',
            ],
        ]);

        return [
            'success' => true,
            'data' => [
                'commission_id' => $commission->id,
                'receptionist_id' => $receptionist->id,
                'type' => $commission->type,
                'points' => $commission->points,
                'amount' => (float) $commission->amount,
                'currency' => $commission->currency,
                'status' => $commission->status,
                'summary' => $this->buildReceptionistCommissionSummary($receptionist->id),
            ],
        ];
    }

    private function receptionistPayoutRequest(array $args): array
    {
        $receptionist = Usuario::findOrFail($args['receptionist_id']);
        $summary = $this->buildReceptionistCommissionSummary($receptionist->id);
        $amount = isset($args['amount']) ? (float) $args['amount'] : (float) $summary['pending_amount'];

        if ($amount <= 0) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'NO_PENDING_PAYOUT',
                    'message' => 'No pending commission amount available for payout',
                ],
            ];
        }

        $payout = ReceptionistPayout::create([
            'receptionist_id' => $receptionist->id,
            'amount' => $amount,
            'currency' => 'EUR',
            'method' => $args['method'] ?? 'revolut',
            'status' => ($args['method'] ?? 'revolut') === 'manual' ? 'requested' : 'pending_provider',
            'requested_at' => now(),
            'metadata' => [
                'source' => 'mcp',
                'pending_points' => $summary['pending_points'],
            ],
        ]);

        ReceptionistCommission::query()
            ->where('receptionist_id', $receptionist->id)
            ->where('status', 'pending')
            ->where('amount', '>', 0)
            ->limit(1000)
            ->update([
                'status' => 'payout_requested',
            ]);

        return [
            'success' => true,
            'data' => [
                'payout_id' => $payout->id,
                'receptionist_id' => $receptionist->id,
                'amount' => (float) $payout->amount,
                'currency' => $payout->currency,
                'method' => $payout->method,
                'status' => $payout->status,
                'provider_reference' => $payout->provider_reference,
            ],
        ];
    }

    // ===== IMPLEMENTACIÓN DE ESTIMACIONES =====

    private function priceEstimate(array $args): array
    {
        // Estimación básica basada en distancia (simulada)
        $distance = $args['distance_km'] ?? 10; // Default 10km
        $basePrice = 3.50; // Precio base
        $pricePerKm = 1.20; // Precio por km

        $estimatedPrice = $basePrice + ($distance * $pricePerKm);

        return [
            'success' => true,
            'data' => [
                'pickup_location' => $args['pickup_location'],
                'dropoff_location' => $args['dropoff_location'],
                'distance_km' => $distance,
                'estimated_price' => round($estimatedPrice, 2),
                'currency' => 'EUR',
            ],
        ];
    }

    // ===== MÉTODOS AUXILIARES =====

    private function getHotelServicesCount(int $hotelId, string $period): int
    {
        $query = Servicio::where('usuario_id', $hotelId);

        if ($period === 'today') {
            $query->whereDate('fecha_servicio', today());
        } elseif ($period === 'month') {
            $query->whereMonth('fecha_servicio', now()->month);
        } elseif ($period === 'week') {
            $query->whereBetween('fecha_servicio', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'year') {
            $query->whereYear('fecha_servicio', now()->year);
        }

        return $query->count();
    }

    private function getHotelReservationsCount(int $hotelId, string $period): int
    {
        $query = Booking::whereHas('servicio', function ($q) use ($hotelId) {
            $q->where('usuario_id', $hotelId);
        });

        if ($period === 'today') {
            $query->whereDate('bookingDate', today());
        } elseif ($period === 'month') {
            $query->whereMonth('bookingDate', now()->month);
        }

        return $query->count();
    }

    private function getHotelRevenue(int $hotelId, string $period): float
    {
        // Implementar cálculo de revenue real
        return 0.0;
    }

    private function buildReceptionistCommissionSummary(int $receptionistId): array
    {
        $baseQuery = ReceptionistCommission::query()
            ->where('receptionist_id', $receptionistId);

        $pendingQuery = (clone $baseQuery)->where('status', 'pending');
        $paidQuery = (clone $baseQuery)->where('status', 'paid');

        return [
            'pending_points' => (int) $pendingQuery->sum('points'),
            'pending_amount' => (float) $pendingQuery->sum('amount'),
            'paid_points' => (int) $paidQuery->sum('points'),
            'paid_amount' => (float) $paidQuery->sum('amount'),
            'today_amount' => (float) (clone $baseQuery)->whereDate('created_at', today())->sum('amount'),
            'month_amount' => (float) (clone $baseQuery)->whereMonth('created_at', now()->month)->sum('amount'),
            'currency' => 'EUR',
        ];
    }

    private function calculateReceptionistCommissionAmount(string $type, float $purchaseAmount): float
    {
        return match ($type) {
            'app_download' => 5.0,
            'chatbot_purchase' => round($purchaseAmount * 0.10, 2),
            'taxi_booking' => max(1.0, round($purchaseAmount * 0.05, 2)),
            default => 0.0,
        };
    }

    private function calculateReceptionistCommissionPoints(float $amount): int
    {
        return (int) round($amount * 10);
    }

    private function callAurigaAPI(array $data): array
    {
        // Simulación de llamada a API Auriga
        // En producción, esto sería una llamada real HTTP

        return [
            'booking_id' => 'AUR-'.time(),
            'estimated_price' => 15.50,
            'eta' => '15 min',
        ];
    }
}
