<?php

namespace App\Filament\App\Pages;

use App\Models\Taxi\Device;
use App\Services\TraccarService;
use App\Services\GeocodingService;
use App\Support\TrackingConnectivity;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Schema as Infolist;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;

use UnitEnum;
use BackedEnum;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class MapPage extends Page implements HasActions, HasForms, HasInfolists
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|UnitEnum|null $navigationGroup = 'Servicios de Taxista';

    protected static ?string $navigationLabel = 'GPS Mapa';

    protected static ?string $title = 'GPS Mapa';

    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = false;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected string $view = 'filament.pages.map-page';

    public $devices = [];
    public $positions = [];
    public $selectedDevice = null;
    public $selectedPosition = null;


    public function mount(): void
    {
        $this->loadMapData();
    }

    public function loadMapData(): void
    {
        try {

            $traccarService = app(TraccarService::class);

            if ($traccarService->ensureAuthenticated()) {
                \Log::info('MapPage: Traccar authenticated, loading data...');

                $devices = $traccarService->getDevices();

                // ESTRATEGIA MÚLTIPLE: Probar diferentes métodos para obtener datos completos de velocidad
                $positions = [];

                // Método 1: Últimas posiciones con all=true
                try {
                    $positions = $traccarService->getLastPositions();
                    \Log::info('MapPage: Method 1 (getLastPositions) returned: ' . count($positions) . ' positions');
                } catch (\Exception $e) {
                    \Log::error('MapPage: Method 1 failed: ' . $e->getMessage());
                }

                // Método 2: Si no tenemos suficientes datos, probar método alternativo
                if (empty($positions)) {
                    \Log::info('MapPage: Method 1 returned empty, trying Method 2 (getLatestPositions)');
                    try {
                        $positions = $traccarService->getLatestPositions();
                        \Log::info('MapPage: Method 2 returned: ' . count($positions) . ' positions');
                    } catch (\Exception $e) {
                        \Log::error('MapPage: Method 2 failed: ' . $e->getMessage());
                    }
                }

                // Método 3: Fallback al método original
                if (empty($positions)) {
                    \Log::info('MapPage: Both new methods failed, falling back to original getPositions');
                    $positions = $traccarService->getPositions();
                    \Log::info('MapPage: Original method returned: ' . count($positions) . ' positions');
                }

                // Ensure we have arrays
                $this->devices = is_array($devices) ? $devices : [];
                $this->positions = is_array($positions) ? $positions : [];

                // Debug logging with more details
                \Log::info('MapPage: Successfully loaded ' . count($this->devices) . ' devices and ' . count($this->positions) . ' positions');

                if (count($this->devices) > 0) {
                    \Log::info('MapPage: First device: ' . json_encode($this->devices[0]));
                }

                if (count($this->positions) > 0) {
                    \Log::info('MapPage: First position: ' . json_encode($this->positions[0]));

                    // DEBUG: Verificar si las posiciones tienen datos de velocidad
                    $positionsWithSpeed = array_filter($this->positions, function ($pos) {
                        return isset($pos['speed']) && $pos['speed'] !== null;
                    });

                    \Log::info('MapPage: Positions with speed data: ' . count($positionsWithSpeed) . ' out of ' . count($this->positions));

                    if (count($positionsWithSpeed) > 0) {
                        \Log::info('MapPage: Sample position with speed: ' . json_encode($positionsWithSpeed[0]));
                    } else {
                        \Log::warning('MapPage: NO POSITIONS HAVE SPEED DATA! This is the problem.');

                        // Log all available fields in positions to see what we have
                        if (count($this->positions) > 0) {
                            \Log::info('MapPage: Available fields in first position: ' . implode(', ', array_keys($this->positions[0])));
                        }
                    }
                }

                // Add status information to devices if not present
                foreach ($this->devices as &$device) {
                    $position = collect($this->positions)->firstWhere('deviceId', (int) ($device['id'] ?? 0));
                    $lastCommunicationAt = TrackingConnectivity::resolveLastCommunicationAt($position, $device);

                    $device['status'] = TrackingConnectivity::resolveDeviceStatus($position, $device);

                    if ($lastCommunicationAt) {
                        $device['lastUpdate'] = $lastCommunicationAt->toIso8601String();
                    }
                }

                // OPTIMIZACIÓN: Geocodificar de forma limitada para evitar timeout
                \Log::info('MapPage: Enabling limited geocoding to prevent timeout');
                $this->enrichPositionsWithAddressesLimited();

            } else {
                \Log::error('MapPage: Failed to authenticate with Traccar using configured credentials');
                $this->devices = [];
                $this->positions = [];
            }

        } catch (\Exception $e) {
            \Log::error('MapPage: Exception loading data: ' . $e->getMessage());
            \Log::error('MapPage: Stack trace: ' . $e->getTraceAsString());

            $this->devices = [];
            $this->positions = [];
        }
    }

    public function refreshMap(): void
    {
        \Log::info('MapPage: Manual refresh triggered');

        $this->loadMapData();

        // Force Livewire to re-render the component
        $this->dispatch('map-refresh', [
            'devices' => $this->devices,
            'positions' => $this->positions,
            'timestamp' => time()
        ]);

        // Enriquecer con direcciones después del refresh (limitado)
        $this->enrichPositionsWithAddressesLimited();

        \Log::info('MapPage: Manual refresh completed');
    }

    /**
     * Método para polling automático cada 10 segundos
     * Este método se ejecuta automáticamente por wire:poll
     */
    public function autoRefresh(): void
    {
        \Log::info('MapPage: Auto-refresh triggered by wire:poll');

        try {
            // Cargar nuevos datos
            $this->loadMapData();

            // REMOVED: map-auto-refresh dispatch - was causing interference
            // El mapa se actualiza automáticamente con livewire:updated

            \Log::info('MapPage: Auto-refresh completed successfully');

        } catch (\Exception $e) {
            \Log::error('MapPage: Error during auto-refresh: ' . $e->getMessage());
        }
    }

    /**
     * Método para activar geocoding manualmente (sin bloquear la carga inicial)
     */
    public function enableGeocoding(): void
    {
        try {
            \Log::info('MapPage: Manual geocoding requested');

            // Disparar evento de inicio
            $this->js('window.dispatchEvent(new CustomEvent("geocoding-started"))');

            $this->enrichPositionsWithAddresses();

            // Disparar evento de completado
            $this->js('window.dispatchEvent(new CustomEvent("geocoding-completed"))');

            // Notificar al frontend que se actualizaron las direcciones
            $this->dispatch('map-refresh', [
                'devices' => $this->devices,
                'positions' => $this->positions,
                'timestamp' => time()
            ]);

            \Log::info('MapPage: Manual geocoding completed successfully');

        } catch (\Exception $e) {
            \Log::error('MapPage: Error during manual geocoding: ' . $e->getMessage());

            // Disparar evento de error
            $this->js('window.dispatchEvent(new CustomEvent("geocoding-error"))');
        }
    }

    /**
     * Enriquece las posiciones con direcciones geocodificadas (versión limitada para evitar timeout)
     */
    private function enrichPositionsWithAddressesLimited(): void
    {
        try {
            $geocodingService = app(GeocodingService::class);

            // Obtener coordenadas únicas para geocodificar
            $uniqueCoordinates = [];
            foreach ($this->positions as $position) {
                if (isset($position['latitude']) && isset($position['longitude'])) {
                    $key = number_format($position['latitude'], 4) . ',' . number_format($position['longitude'], 4);
                    if (!isset($uniqueCoordinates[$key])) {
                        $uniqueCoordinates[$key] = [
                            'latitude' => $position['latitude'],
                            'longitude' => $position['longitude']
                        ];
                    }
                }
            }

            \Log::info('MapPage: Limited geocoding for ' . count($uniqueCoordinates) . ' unique coordinates');

            // SOLO procesar el primer lote de 20 coordenadas para evitar timeout
            $firstBatch = array_slice(array_values($uniqueCoordinates), 0, 20);

            if (!empty($firstBatch)) {
                \Log::info("MapPage: Processing first batch of " . count($firstBatch) . " coordinates");

                // Obtener direcciones para el primer lote
                $addresses = $geocodingService->getMultipleAddresses($firstBatch);

                // Enriquecer posiciones con direcciones
                $enrichedCount = 0;
                foreach ($this->positions as &$position) {
                    if (isset($position['latitude']) && isset($position['longitude'])) {
                        $key = $position['latitude'] . ',' . $position['longitude'];
                        if (isset($addresses[$key])) {
                            $position['address'] = $addresses[$key];
                            $enrichedCount++;
                        }
                    }
                }

                \Log::info("MapPage: Limited geocoding completed, {$enrichedCount} positions enriched with addresses");

                // Si hay más coordenadas, programar geocodificación completa para después
                if (count($uniqueCoordinates) > 20) {
                    \Log::info("MapPage: " . (count($uniqueCoordinates) - 20) . " coordinates remaining for background processing");
                }
            }

        } catch (\Exception $e) {
            \Log::error('MapPage: Error during limited geocoding: ' . $e->getMessage());
        }
    }

    /**
     * Enriquece las posiciones con direcciones geocodificadas (versión completa)
     */
    private function enrichPositionsWithAddresses(): void
    {
        try {
            $geocodingService = app(GeocodingService::class);

            // Obtener coordenadas únicas para geocodificar
            $uniqueCoordinates = [];
            foreach ($this->positions as $position) {
                if (isset($position['latitude']) && isset($position['longitude'])) {
                    $key = number_format($position['latitude'], 4) . ',' . number_format($position['longitude'], 4);
                    if (!isset($uniqueCoordinates[$key])) {
                        $uniqueCoordinates[$key] = [
                            'latitude' => $position['latitude'],
                            'longitude' => $position['longitude']
                        ];
                    }
                }
            }

            \Log::info('MapPage: Geocoding ' . count($uniqueCoordinates) . ' unique coordinates');

            // Procesar coordenadas en lotes de 20 con pausas reducidas
            $allAddresses = [];
            $coordinateChunks = array_chunk(array_values($uniqueCoordinates), 20);
            $totalChunks = count($coordinateChunks);

            \Log::info("MapPage: Processing {$totalChunks} chunks of coordinates");

            foreach ($coordinateChunks as $chunkIndex => $chunk) {
                \Log::info("MapPage: Processing chunk " . ($chunkIndex + 1) . " of {$totalChunks} (" . count($chunk) . " coordinates)");

                // Obtener direcciones para este lote
                $chunkAddresses = $geocodingService->getMultipleAddresses($chunk);

                // Combinar con el resultado total
                $allAddresses = array_merge($allAddresses, $chunkAddresses);

                // Pausa reducida entre lotes (solo 500ms)
                if ($chunkIndex < $totalChunks - 1) {
                    \Log::info("MapPage: Pausing 500ms before next chunk...");
                    usleep(500000); // 500ms en microsegundos
                }
            }

            \Log::info('MapPage: Completed all chunks, total addresses obtained: ' . count($allAddresses));

            // Enriquecer posiciones con direcciones
            $enrichedCount = 0;
            foreach ($this->positions as &$position) {
                if (isset($position['latitude']) && isset($position['longitude'])) {
                    $key = $position['latitude'] . ',' . $position['longitude'];
                    if (isset($allAddresses[$key])) {
                        $position['address'] = $allAddresses[$key];
                        $enrichedCount++;
                    }
                }
            }

            \Log::info("MapPage: Geocoding completed, {$enrichedCount} positions enriched with addresses");

        } catch (\Exception $e) {
            \Log::error('MapPage: Error during geocoding: ' . $e->getMessage());
        }
    }

    public function showDeviceDetails(int $deviceId): void
    {
        \Log::info('showDeviceDetails called with deviceId: ' . $deviceId);

        // Encontrar el dispositivo seleccionado
        $this->selectedDevice = collect($this->devices)->firstWhere('id', $deviceId);
        $this->selectedPosition = collect($this->positions)->firstWhere('deviceId', $deviceId);

        \Log::info('Found device: ' . json_encode($this->selectedDevice));
        \Log::info('Found position: ' . json_encode($this->selectedPosition));

        if ($this->selectedDevice) {
            $this->mountAction('viewDeviceDetails');
        } else {
            \Log::warning('Device not found with id: ' . $deviceId);
        }
    }

    /**
     * Recibe coordenadas del navegador (incluye simulación de DevTools),
     * detecta la IP de la sesión actual y envía el ping de prueba a Traccar.
     */
    public function ingestBrowserTrackingPing(array $payload): void
    {
        try {
            $latitude = isset($payload['latitude']) ? (float)$payload['latitude'] : null;
            $longitude = isset($payload['longitude']) ? (float)$payload['longitude'] : null;

            if ($latitude === null || $longitude === null) {
                \Log::warning('MapPage: Browser tracking ping ignored (missing coordinates)', [
                    'payload' => $payload,
                ]);
                return;
            }

            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                \Log::warning('MapPage: Browser tracking ping ignored (invalid coordinates)', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);
                return;
            }

            $userId = Auth::id();
            if (!$userId) {
                \Log::warning('MapPage: Browser tracking ping ignored (no authenticated user)');
                return;
            }

            $deviceId = $this->resolveCurrentUserTraccarDeviceId((int)$userId);
            if (!$deviceId) {
                \Log::warning('MapPage: Browser tracking ping ignored (no Traccar device for user)', [
                    'user_id' => $userId,
                ]);
                return;
            }

            $request = Request::instance();
            $clientIp = $request->ip();
            $forwardedFor = $request->header('x-forwarded-for');
            $userAgent = $request->userAgent();

            $speedMs = isset($payload['speed_ms']) ? max(0, (float)$payload['speed_ms']) : 0.0;
            $speedKnots = round($speedMs * 1.94384449, 3);
            $heading = isset($payload['heading']) ? (float)$payload['heading'] : 0.0;
            $accuracy = isset($payload['accuracy']) ? (float)$payload['accuracy'] : null;
            $altitude = isset($payload['altitude']) ? (float)$payload['altitude'] : 0.0;

            $traccarService = app(TraccarService::class);
            $result = $traccarService->newRealtimePosition([
                'device_id' => $deviceId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'altitude' => $altitude,
                'speed' => $speedKnots,
                'course' => $heading,
                'address' => $payload['address'] ?? 'Browser simulated location',
                'attributes' => [
                    'source' => 'browser_geolocation',
                    'ip' => $clientIp,
                    'forwarded_for' => $forwardedFor,
                    'accuracy' => $accuracy,
                    'user_agent' => $userAgent,
                    'auth_user_id' => $userId,
                ],
            ]);

            if (!$result) {
                \Log::warning('MapPage: Browser tracking ping failed when sending to Traccar', [
                    'user_id' => $userId,
                    'device_id' => $deviceId,
                    'ip' => $clientIp,
                ]);
                return;
            }

            \Log::info('MapPage: Browser tracking ping sent', [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'ip' => $clientIp,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        } catch (\Throwable $e) {
            \Log::error('MapPage: Browser tracking ping exception: ' . $e->getMessage(), [
                'payload' => $payload,
            ]);
        }
    }

    private function resolveCurrentUserTraccarDeviceId(int $userId): ?int
    {
        // Preferir datos ya cargados en la página para evitar llamadas adicionales.
        if (is_array($this->devices) && !empty($this->devices)) {
            foreach ($this->devices as $device) {
                if (
                    isset($device['id'], $device['uniqueId']) &&
                    (string)$device['uniqueId'] === (string)$userId
                ) {
                    return (int)$device['id'];
                }
            }
        }

        // Fallback: buscar mapeo local.
        $localDevice = Device::query()
            ->where('usuario_id', $userId)
            ->orWhere('unique_id', (string)$userId)
            ->first();

        if ($localDevice?->traccar_id) {
            return (int)$localDevice->traccar_id;
        }

        // Último fallback: consultar Traccar para el usuario actual.
        try {
            $traccarService = app(TraccarService::class);
            $remoteDevices = $traccarService->getDevices();

            foreach ($remoteDevices as $device) {
                if (
                    isset($device['id'], $device['uniqueId']) &&
                    (string)$device['uniqueId'] === (string)$userId
                ) {
                    return (int)$device['id'];
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('MapPage: Unable to resolve device from Traccar: ' . $e->getMessage(), [
                'user_id' => $userId,
            ]);
        }

        return null;
    }

    public function getActions(): array
    {
        return [
            $this->viewDeviceDetailsAction(),
        ];
    }

    public function geocodeAllAddressesAction(): Action
    {
        return Action::make('geocodeAllAddresses')
            ->label('Geocodificar Todas las Direcciones')
            ->icon('heroicon-o-map-pin')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Geocodificar Todas las Direcciones')
            ->modalDescription('Este proceso puede tomar varios minutos para procesar todas las coordenadas. ¿Deseas continuar?')
            ->modalSubmitActionLabel('Sí, Geocodificar Todo')
            ->action(function () {
                try {
                    $this->enrichPositionsWithAddresses();

                    // Refrescar el mapa con las nuevas direcciones
                    $this->dispatch('map-refresh', [
                        'devices' => $this->devices,
                        'positions' => $this->positions,
                        'timestamp' => time()
                    ]);

                    Notification::make()
                        ->title('Geocodificación Completada')
                        ->body('Todas las direcciones han sido procesadas exitosamente.')
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    \Log::error('Error during manual geocoding: ' . $e->getMessage());

                    Notification::make()
                        ->title('Error en Geocodificación')
                        ->body('Ocurrió un error durante el proceso: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function viewDeviceDetailsAction(): Action
    {
        return Action::make('viewDeviceDetails')
            ->modalHeading('Informaciónnnn del Vehículo')
            ->modalDescription('Detalles del dispositivo y posición')
            ->modalWidth(Width::Medium)
            ->icon('heroicon-o-truck')
            ->modalIcon('heroicon-o-truck')
            ->slideOver()
            ->modalContent(fn() => view('filament.modals.device-details', [
                'device' => $this->selectedDevice,
                'position' => $this->selectedPosition,
            ]))
            ->modalFooterActions([
                Action::make('viewMoreDetails')
                    ->label('Ver Más Detalles')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn() => $this->selectedDevice ? route('filament.admin.resources.devices.edit', $this->selectedDevice['id']) : '#')
                    ->openUrlInNewTab(),
                Action::make('close')
                    ->label('Cerrar')
                    ->color('gray')
                    ->modalCancelAction(),
            ])
            ->closeModalByClickingAway(false);
    }
}
