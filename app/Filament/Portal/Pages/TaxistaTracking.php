<?php

declare(strict_types=1);

namespace App\Filament\Portal\Pages;

use App\Filament\Pages\CarTracking;
use App\Models\Taxista;
use App\Models\Taxi\Device;
use App\Models\Taxi\Taxi as LegacyTaxi;
use App\Models\Taxi\Taxista as LegacyTaxista;
use App\Models\TaxistaTaxi;
use App\Support\TrackingConnectivity;
use App\Services\TraccarService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema as DbSchema;

class TaxistaTracking extends CarTracking
{
    protected static ?string $navigationLabel = 'Mapa Taxis';

    protected static ?string $title = 'Mapa Taxis';

    protected static ?string $slug = 'mapa-taxis';

    protected static ?int $navigationSort = 3;

    public bool $trackingSimulationEnabled = false;

    public string $visibleTaxiScope = 'all';

    /** @var array<int, string> */
    public array $expectedTrackingIdentifiersSample = [];

    /** @var array<int, array{name: string, uniqueId: string}> */
    public array $remoteDeviceIdentitySample = [];

    public ?string $trackingUserHint = null;

    public function mount(): void
    {
        parent::mount();

        $this->trackingSimulationEnabled = (bool) session('portal.tracking.simulation_enabled', false);
        $this->visibleTaxiScope = $this->normalizeTaxiVisibilityScope((string) session('portal.tracking.visible_taxi_scope', 'all'));
    }

    public function getActions(): array
    {
        return array_merge(parent::getActions(), [
            $this->setTaxiScopeAction('one', 'Ver 1 taxi'),
            $this->setTaxiScopeAction('two', 'Ver 2 taxis'),
            $this->setTaxiScopeAction('all', 'Ver todos'),
            $this->toggleTrackingSimulationAction(),
            $this->sendSimulationPingAction(),
            $this->showTrackingStatsAction(),
        ]);
    }

    public function loadMapData(): void
    {
        try {
            $taxistaUserId = auth('taxista')->id() ?? auth('web')->id();

            if (! $taxistaUserId) {
                $this->devices = [];
                $this->positions = [];
                $this->traccarAuthenticated = false;
                $this->remoteDevicesCount = 0;
                $this->remotePositionsCount = 0;
                $this->visibleDevicesCount = 0;
                $this->visiblePositionsCount = 0;
                $this->allowedIdentifiersSample = [];
                $this->expectedTrackingIdentifiersSample = [];
                $this->remoteDeviceIdentitySample = [];
                $this->trackingUserHint = null;

                return;
            }

            $traccarService = app(TraccarService::class);

            if (! $traccarService->ensureAuthenticated()) {
                \Log::error('TaxistaTracking: failed to authenticate with Traccar');
                $this->devices = [];
                $this->positions = [];
                $this->traccarAuthenticated = false;
                $this->remoteDevicesCount = 0;
                $this->remotePositionsCount = 0;
                $this->visibleDevicesCount = 0;
                $this->visiblePositionsCount = 0;
                $this->allowedIdentifiersSample = [];
                $this->expectedTrackingIdentifiersSample = [];
                $this->remoteDeviceIdentitySample = [];
                $this->trackingUserHint = 'No se pudo conectar con Traccar. Revisa usuario y password en configuracion.';

                return;
            }

            $this->traccarAuthenticated = true;

            $allowed = $this->resolveAllowedDeviceReferences((int) $taxistaUserId);

            $this->allowedIdentifiersSample = array_values(array_slice($allowed['identifiers'], 0, 10));
            $this->expectedTrackingIdentifiersSample = array_values(array_slice($allowed['taxi_identifiers'], 0, 10));
            $this->remoteDeviceIdentitySample = [];
            $this->trackingUserHint = null;

            \Log::info('TaxistaTracking: allowed device references resolved', [
                'taxista_user_id' => $taxistaUserId,
                'allowed_traccar_ids_count' => count($allowed['traccar_ids']),
                'allowed_identifiers_count' => count($allowed['identifiers']),
                'allowed_identifiers_sample' => array_slice($allowed['identifiers'], 0, 10),
            ]);

            $remoteDevices = $traccarService->getDevices();
            $remotePositions = $traccarService->getLastPositions();

            $this->remoteDevicesCount = is_array($remoteDevices) ? count($remoteDevices) : 0;
            $this->remotePositionsCount = is_array($remotePositions) ? count($remotePositions) : 0;

            \Log::info('TaxistaTracking: traccar payload fetched', [
                'taxista_user_id' => $taxistaUserId,
                'remote_devices_count' => is_array($remoteDevices) ? count($remoteDevices) : 0,
                'remote_positions_count' => is_array($remotePositions) ? count($remotePositions) : 0,
            ]);

            if (empty($remotePositions)) {
                $remotePositions = $traccarService->getLatestPositions();
            }

            if (empty($remotePositions)) {
                $remotePositions = $traccarService->getPositions();
            }

            $allowedTraccarIds = array_flip($allowed['traccar_ids']);
            $allowedIdentifiers = array_flip($allowed['identifiers']);

            $devices = array_values(array_filter(
                is_array($remoteDevices) ? $remoteDevices : [],
                fn (array $device): bool => $this->isRemoteDeviceAllowed($device, $allowedTraccarIds, $allowedIdentifiers)
            ));

            $this->visibleDevicesCount = count($devices);

            if ($devices === [] && is_array($remoteDevices) && $remoteDevices !== []) {
                $examples = collect($remoteDevices)
                    ->take(5)
                    ->map(fn (array $device): array => [
                        'id' => $device['id'] ?? null,
                        'uniqueId' => $device['uniqueId'] ?? null,
                        'name' => $device['name'] ?? null,
                    ])
                    ->values()
                    ->all();

                $this->remoteDeviceIdentitySample = collect($examples)
                    ->map(static fn (array $item): array => [
                        'name' => (string) ($item['name'] ?? ''),
                        'uniqueId' => (string) ($item['uniqueId'] ?? ''),
                    ])
                    ->values()
                    ->all();

                $this->trackingUserHint = $this->buildTrackingUserHint(
                    $this->expectedTrackingIdentifiersSample,
                    $this->remoteDeviceIdentitySample,
                );

                \Log::warning('TaxistaTracking: remote devices available but none matched allowed identifiers', [
                    'taxista_user_id' => $taxistaUserId,
                    'remote_devices_sample' => $examples,
                ]);
            }

            $visibleDeviceIds = array_flip(array_map(
                static fn (array $device): int => (int) ($device['id'] ?? 0),
                $devices
            ));

            $positions = array_values(array_filter(
                is_array($remotePositions) ? $remotePositions : [],
                static fn (array $position): bool => isset($visibleDeviceIds[(int) ($position['deviceId'] ?? 0)])
            ));

            $positions = array_values(TrackingConnectivity::indexLatestPositionsByDevice($positions));
            ['devices' => $devices, 'positions' => $positions] = $this->applyLocalTrackingSnapshots(
                $devices,
                $positions,
            );

            $this->visiblePositionsCount = count($positions);

            $latestPositionsByDevice = TrackingConnectivity::indexLatestPositionsByDevice($positions);

            foreach ($devices as &$device) {
                $position = $latestPositionsByDevice[(int) ($device['id'] ?? 0)] ?? null;
                $lastCommunicationAt = TrackingConnectivity::resolveLastCommunicationAt($position, $device);

                $device['status'] = TrackingConnectivity::resolveDeviceStatus($position, $device);

                if ($lastCommunicationAt) {
                    $device['lastUpdate'] = $lastCommunicationAt->toIso8601String();
                }
            }

            $this->devices = $devices;
            $this->positions = $positions;
        } catch (\Throwable $exception) {
            \Log::error('TaxistaTracking: exception loading data', [
                'error' => $exception->getMessage(),
            ]);

            $this->devices = [];
            $this->positions = [];
            $this->traccarAuthenticated = false;
            $this->remoteDevicesCount = 0;
            $this->remotePositionsCount = 0;
            $this->visibleDevicesCount = 0;
            $this->visiblePositionsCount = 0;
            $this->allowedIdentifiersSample = [];
            $this->expectedTrackingIdentifiersSample = [];
            $this->remoteDeviceIdentitySample = [];
            $this->trackingUserHint = 'Ocurrio un error cargando el seguimiento. Reintenta en unos segundos.';
        }
    }

    /**
     * @return array{traccar_ids: array<int, int>, identifiers: array<int, string>, taxi_identifiers: array<int, string>}
     */
    private function resolveAllowedDeviceReferences(int $taxistaUserId): array
    {
        $candidateUserIds = $this->resolveCandidateUserIds($taxistaUserId);

        if (! DbSchema::hasTable('devices')) {
            return [
                'traccar_ids' => [],
                'identifiers' => [],
                'taxi_identifiers' => [],
            ];
        }

        $taxiIds = [];
        $taxiPlateIdentifiers = [];
        $taxiTrackingIdentifiers = [];

        if (DbSchema::hasTable('taxista_taxis')) {
            $columns = ['id', 'license_plate'];
            if (DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')) {
                $columns[] = 'tracking_uuid';
            }

            $taxiRows = TaxistaTaxi::query()
                ->where('taxista_user_id', $taxistaUserId)
                ->orderByDesc('id')
                ->get($columns);

            $taxiRows = $this->applyTaxiVisibilityScopeToRows($taxiRows);

            $taxiIds = $taxiRows
                ->pluck('id')
                ->filter(static fn ($id): bool => is_numeric($id) && (int) $id > 0)
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all();

            $taxiPlateIdentifiers = $taxiRows
                ->pluck('license_plate')
                ->filter(static fn ($plate): bool => is_string($plate) && trim($plate) !== '')
                ->map(fn (string $plate): string => $this->normalizeIdentifier($plate))
                ->filter(static fn (string $plate): bool => $plate !== '')
                ->values()
                ->all();

            if (DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')) {
                $taxiTrackingIdentifiers = $taxiRows
                    ->pluck('tracking_uuid')
                    ->filter(static fn ($uuid): bool => is_string($uuid) && trim($uuid) !== '')
                    ->map(fn (string $uuid): string => $this->normalizeIdentifier($uuid))
                    ->filter(static fn (string $uuid): bool => $uuid !== '')
                    ->values()
                    ->all();
            }
        }

        if (DbSchema::hasTable('taxis') && DbSchema::hasColumn('taxis', 'usuario_id')) {
            $legacyTaxiColumns = $this->resolveLegacyTaxiColumns();

            $legacyTaxiRows = LegacyTaxi::query()
                ->whereIn('usuario_id', $candidateUserIds)
                ->get($legacyTaxiColumns);

            $taxiIds = array_values(array_unique(array_merge(
                $taxiIds,
                $legacyTaxiRows
                    ->pluck('id')
                    ->filter(static fn ($id): bool => is_numeric($id) && (int) $id > 0)
                    ->map(static fn ($id): int => (int) $id)
                    ->values()
                    ->all()
            )));

            $taxiPlateIdentifiers = array_values(array_unique(array_merge(
                $taxiPlateIdentifiers,
                $legacyTaxiRows
                    ->pluck('matricula')
                    ->merge($legacyTaxiRows->pluck('licencia'))
                    ->filter(static fn ($plate): bool => is_string($plate) && trim($plate) !== '')
                    ->map(fn (string $plate): string => $this->normalizeIdentifier($plate))
                    ->filter(static fn (string $plate): bool => $plate !== '')
                    ->values()
                    ->all()
            )));
        }

        $query = Device::query()->whereRaw('1 = 0');

        if ($taxiIds !== []) {
            $query->orWhereIn('taxi_id', $taxiIds);
        }

        if ($taxiTrackingIdentifiers !== []) {
            $query->orWhereIn('unique_id', $taxiTrackingIdentifiers);
        }

        if ($taxiPlateIdentifiers !== []) {
            $query
                ->orWhereIn('name', $taxiPlateIdentifiers)
                ->orWhereIn('unique_id', $taxiPlateIdentifiers);
        }

        $mappedDevices = $query->get(['traccar_id', 'unique_id', 'name']);

        $traccarIds = $mappedDevices
            ->pluck('traccar_id')
            ->filter(static fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $mappedIdentifiers = $mappedDevices
            ->pluck('unique_id')
            ->filter(static fn ($id): bool => is_string($id) && $id !== '')
            ->map(fn (string $id): string => $this->normalizeIdentifier($id))
            ->merge(
                $mappedDevices
                    ->pluck('name')
                    ->filter(static fn ($name): bool => is_string($name) && $name !== '')
                    ->map(fn (string $name): string => $this->normalizeIdentifier($name))
            )
            ->merge($taxiPlateIdentifiers)
            ->merge($taxiTrackingIdentifiers)
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();

        $taxiIdentifiers = collect($taxiPlateIdentifiers)
            ->merge($taxiTrackingIdentifiers)
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();

        return [
            'traccar_ids' => $traccarIds,
            'identifiers' => $mappedIdentifiers,
            'taxi_identifiers' => $taxiIdentifiers,
        ];
    }

    /**
     * @param array<int, string> $taxiIdentifiers
     * @param array<int, array{name: string, uniqueId: string}> $remoteDeviceIdentities
     */
    private function buildTrackingUserHint(array $taxiIdentifiers, array $remoteDeviceIdentities): string
    {
        if ($taxiIdentifiers === []) {
            return 'No hay identificadores de seguimiento en tus taxis. Completa Tracking UUID en App > Taxista > Taxis.';
        }

        if ($remoteDeviceIdentities === []) {
            return 'No llegaron dispositivos desde Traccar. Verifica que el cliente Traccar este enviando ubicacion.';
        }

        return 'Tus taxis tienen identificadores que no coinciden con los UUID de Traccar. Copia el Identificador de dispositivo del cliente Traccar en Tracking UUID de cada taxi.';
    }

    /**
     * @return array<int, int>
     */
    private function resolveCandidateUserIds(int $taxistaUserId): array
    {
        $candidateIds = [$taxistaUserId];

        $portalTaxista = $this->findPortalTaxista($taxistaUserId);
        if ($portalTaxista && DbSchema::hasColumn('users', 'usuario_id')) {
            $legacyUsuarioId = $portalTaxista->getAttribute('usuario_id');

            if (is_numeric($legacyUsuarioId)) {
                $candidateIds[] = (int) $legacyUsuarioId;
            }
        }

        if ($portalTaxista && DbSchema::hasColumn('users', 'usuario')) {
            $legacyUsuarioId = $portalTaxista->getAttribute('usuario');

            if (is_numeric($legacyUsuarioId)) {
                $candidateIds[] = (int) $legacyUsuarioId;
            }
        }

        if ($portalTaxista && DbSchema::hasTable('usuarios')) {
            $legacyMatcher = $this->resolveLegacyMatcherForUsuarios($portalTaxista);

            $legacyCandidateIds = [];

            if ($legacyMatcher !== []) {
                $legacyCandidateIds = LegacyTaxista::query()
                    ->where(function ($query) use ($legacyMatcher): void {
                        foreach ($legacyMatcher as $column => $value) {
                            $query->orWhere($column, $value);
                        }
                    })
                    ->pluck('id')
                    ->filter(static fn ($id): bool => is_numeric($id) && (int) $id > 0)
                    ->map(static fn ($id): int => (int) $id)
                    ->values()
                    ->all();
            }

            $candidateIds = array_merge($candidateIds, $legacyCandidateIds);
        }

        $candidateIds = array_values(array_unique(array_filter(
            $candidateIds,
            static fn ($id): bool => is_int($id) && $id > 0
        )));

        return $candidateIds === [] ? [$taxistaUserId] : $candidateIds;
    }

    /**
     * @return array<string, string>
     */
    private function resolveLegacyMatcherForUsuarios(Taxista $portalTaxista): array
    {
        $matcher = [];

        if (DbSchema::hasColumn('usuarios', 'email') && filled($portalTaxista->email ?? null)) {
            $matcher['email'] = (string) $portalTaxista->email;
        }

        if (DbSchema::hasColumn('usuarios', 'nif') && filled($portalTaxista->nif ?? null)) {
            $matcher['nif'] = (string) $portalTaxista->nif;
        }

        if (DbSchema::hasColumn('usuarios', 'licencia') && filled($portalTaxista->licencia ?? null)) {
            $matcher['licencia'] = (string) $portalTaxista->licencia;
        }

        if (DbSchema::hasColumn('usuarios', 'nombre') && filled($portalTaxista->name ?? null)) {
            $matcher['nombre'] = (string) $portalTaxista->name;
        }

        return $matcher;
    }

    /**
     * @return array<int, string>
     */
    private function resolveLegacyTaxiColumns(): array
    {
        $columns = ['id'];

        if (DbSchema::hasColumn('taxis', 'matricula')) {
            $columns[] = 'matricula';
        }

        if (DbSchema::hasColumn('taxis', 'licencia')) {
            $columns[] = 'licencia';
        }

        return $columns;
    }

    protected function findPortalTaxista(int $taxistaUserId): ?Taxista
    {
        return Taxista::query()->find($taxistaUserId);
    }

    /**
     * @param array<string, mixed> $device
     * @param array<int, int> $allowedTraccarIds
     * @param array<string, int> $allowedIdentifiers
     */
    private function isRemoteDeviceAllowed(array $device, array $allowedTraccarIds, array $allowedIdentifiers): bool
    {
        $deviceId = (int) ($device['id'] ?? 0);
        if ($deviceId > 0 && isset($allowedTraccarIds[$deviceId])) {
            return true;
        }

        $uniqueId = $this->normalizeIdentifier((string) ($device['uniqueId'] ?? ''));
        if ($uniqueId !== '' && isset($allowedIdentifiers[$uniqueId])) {
            return true;
        }

        $name = $this->normalizeIdentifier((string) ($device['name'] ?? ''));

        if ($name !== '' && isset($allowedIdentifiers[$name])) {
            return true;
        }

        return false;
    }

    private function normalizeIdentifier(string $value): string
    {
        return Str::of($value)
            ->trim()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->toString();
    }

    private function toggleTrackingSimulationAction(): Action
    {
        return Action::make('toggleTrackingSimulation')
            ->label(fn (): string => $this->trackingSimulationEnabled ? 'Desactivar simulacion' : 'Activar simulacion')
            ->icon('heroicon-o-bolt')
            ->color(fn (): string => $this->trackingSimulationEnabled ? 'warning' : 'primary')
            ->action(function (): void {
                $this->trackingSimulationEnabled = ! $this->trackingSimulationEnabled;

                session(['portal.tracking.simulation_enabled' => $this->trackingSimulationEnabled]);

                Notification::make()
                    ->title($this->trackingSimulationEnabled ? 'Simulacion de tracking activada' : 'Simulacion de tracking desactivada')
                    ->success()
                    ->send();
            });
    }

    private function setTaxiScopeAction(string $scope, string $label): Action
    {
        return Action::make('visibleTaxiScope_'.$scope)
            ->label($label)
            ->icon('heroicon-o-funnel')
            ->color(fn (): string => $this->visibleTaxiScope === $scope ? 'primary' : 'gray')
            ->action(function () use ($scope): void {
                $this->setTaxiVisibilityScope($scope);
            });
    }

    private function setTaxiVisibilityScope(string $scope): void
    {
        $this->visibleTaxiScope = $this->normalizeTaxiVisibilityScope($scope);

        session(['portal.tracking.visible_taxi_scope' => $this->visibleTaxiScope]);

        $this->loadMapData();

        $this->dispatch('map-refresh', [
            'devices' => $this->devices,
            'positions' => $this->positions,
            'timestamp' => time(),
        ]);

        Notification::make()
            ->title('Vista de taxis actualizada')
            ->body(match ($this->visibleTaxiScope) {
                'one' => 'Mostrando 1 taxi asignado',
                'two' => 'Mostrando 2 taxis asignados',
                default => 'Mostrando todos los taxis asignados',
            })
            ->success()
            ->send();
    }

    private function normalizeTaxiVisibilityScope(string $scope): string
    {
        return in_array($scope, ['one', 'two', 'all'], true) ? $scope : 'all';
    }

    private function applyTaxiVisibilityScopeToRows(Collection $taxiRows): Collection
    {
        if ($this->visibleTaxiScope === 'one') {
            return $taxiRows->take(1)->values();
        }

        if ($this->visibleTaxiScope === 'two') {
            return $taxiRows->take(2)->values();
        }

        return $taxiRows;
    }

    private function sendSimulationPingAction(): Action
    {
        return Action::make('sendSimulationPing')
            ->label('Enviar ping simulado')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->visible(fn (): bool => $this->trackingSimulationEnabled)
            ->action(function (): void {
                $sent = $this->sendSimulatedPing();

                Notification::make()
                    ->title($sent ? 'Ping simulado enviado' : 'No se pudo enviar ping simulado')
                    ->color($sent ? 'success' : 'danger')
                    ->send();
            });
    }

    private function showTrackingStatsAction(): Action
    {
        return Action::make('showTrackingStats')
            ->label('Ver estado tracking')
            ->icon('heroicon-o-chart-bar')
            ->color('gray')
            ->action(function (): void {
                $total = count($this->devices);
                $online = collect($this->devices)->where('status', 'online')->count();
                $withPosition = collect($this->positions)->filter(fn (array $position): bool => isset($position['latitude'], $position['longitude']))->count();

                Notification::make()
                    ->title('Estado de seguimiento')
                    ->body("Dispositivos: {$total} | Online: {$online} | Con posicion: {$withPosition}")
                    ->success()
                    ->send();
            });
    }

    private function sendSimulatedPing(): bool
    {
        if ($this->devices === [] || $this->positions === []) {
            $this->loadMapData();
        }

        $basePosition = collect($this->positions)
            ->first(fn (array $position): bool => isset($position['latitude'], $position['longitude']));

        if (! is_array($basePosition)) {
            return false;
        }

        $deviceId = (int) ($basePosition['deviceId'] ?? 0);

        if ($deviceId <= 0) {
            return false;
        }

        $latitude = (float) $basePosition['latitude'] + (random_int(-15, 15) / 100000);
        $longitude = (float) $basePosition['longitude'] + (random_int(-15, 15) / 100000);

        $sent = app(TraccarService::class)->sendPositionToTraccar(
            traccarDeviceId: $deviceId,
            latitude: $latitude,
            longitude: $longitude,
            recordedAt: now(),
            speedKmh: (float) random_int(10, 40),
            heading: random_int(0, 360),
            address: 'Simulacion portal taxista',
            attributes: ['source' => 'portal-taxista-simulation'],
        );

        if ($sent) {
            $this->loadMapData();

            $this->dispatch('map-refresh', [
                'devices' => $this->devices,
                'positions' => $this->positions,
                'timestamp' => time(),
            ]);
        }

        return $sent;
    }
}
