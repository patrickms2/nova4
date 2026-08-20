<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TrackingReplayRequest;
use App\Models\TaxistaTaxi;
use App\Models\Taxi\Position;
use App\Services\TraccarService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

final class TrackingReplayController extends Controller
{
    public function __invoke(TrackingReplayRequest $request): View
    {
        $defaultWindowHours = max(1, (int) config('traccar.sync.default_window_hours', 24));
        $requestedTo = $request->string('to')->toString();
        $requestedFrom = $request->string('from')->toString();
        $embed = $request->boolean('embed');

        $to = $requestedTo !== ''
            ? CarbonImmutable::parse($requestedTo)
            : CarbonImmutable::now();
        $from = $requestedFrom !== ''
            ? CarbonImmutable::parse($requestedFrom)
            : $to->subHours($defaultWindowHours);

        $uniqueId = trim($request->string('uniqueId')->toString());
        $device = null;
        $routePoints = [];
        $message = null;

        if ($uniqueId !== '') {
            if (! $this->canAccessReplayForUniqueId($uniqueId)) {
                $message = 'No puedes acceder al replay de ese taxi.';

                return view('tracking.replay', [
                    'defaultWindowHours' => $defaultWindowHours,
                    'device' => $device,
                    'embed' => $embed,
                    'from' => $from,
                    'fromInput' => $from->format('Y-m-d\TH:i'),
                    'message' => $message,
                    'routePoints' => $routePoints,
                    'to' => $to,
                    'toInput' => $to->format('Y-m-d\TH:i'),
                    'uniqueId' => $uniqueId,
                ]);
            }

            /** @var TraccarService $traccarService */
            $traccarService = app(TraccarService::class);
            $device = $traccarService->findTraccarDeviceByUniqueId($uniqueId);

            if (is_array($device) && (int) ($device['id'] ?? 0) > 0) {
                $routePoints = $traccarService->getRouteReport(
                    deviceId: (int) $device['id'],
                    from: $from,
                    to: $to,
                );

                if ($routePoints === []) {
                    $routePoints = $this->getLocalRoutePoints(
                        deviceId: (int) ($device['id'] ?? 0),
                        from: $from,
                        to: $to,
                    );

                    if ($routePoints === []) {
                        $message = 'No se encontraron puntos en el rango seleccionado.';
                    } else {
                        $message = 'Mostrando historial local porque Traccar no devolvió puntos para ese rango.';
                    }
                }
            } else {
                $message = 'No se encontró el dispositivo de Traccar para el código indicado.';
            }
        } else {
            $message = 'Selecciona un taxi con tracking para abrir el replay.';
        }

        return view('tracking.replay', [
            'defaultWindowHours' => $defaultWindowHours,
            'device' => $device,
            'embed' => $embed,
            'from' => $from,
            'fromInput' => $from->format('Y-m-d\TH:i'),
            'message' => $message,
            'routePoints' => $routePoints,
            'to' => $to,
            'toInput' => $to->format('Y-m-d\TH:i'),
            'uniqueId' => $uniqueId,
        ]);
    }

    protected function canAccessReplayForUniqueId(string $uniqueId): bool
    {
        if (! auth('taxista')->check()) {
            return true;
        }

        $allowedUniqueIds = $this->allowedTrackingUniqueIdsForCurrentTaxista();

        if ($allowedUniqueIds === []) {
            return false;
        }

        return $this->uniqueIdIsAllowedForTaxista($uniqueId, $allowedUniqueIds);
    }

    /**
     * @return array<int, string>
     */
    protected function allowedTrackingUniqueIdsForCurrentTaxista(): array
    {
        $taxistaUserId = auth('taxista')->id();

        if (! $taxistaUserId) {
            return [];
        }

        return TaxistaTaxi::query()
            ->where('taxista_user_id', (int) $taxistaUserId)
            ->whereNotNull('tracking_uuid')
            ->pluck('tracking_uuid')
            ->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $allowedUniqueIds
     */
    protected function uniqueIdIsAllowedForTaxista(string $uniqueId, array $allowedUniqueIds): bool
    {
        return in_array($uniqueId, $allowedUniqueIds, true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getLocalRoutePoints(int $deviceId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        if ($deviceId <= 0 || ! Schema::hasTable('positions')) {
            return [];
        }

        return Position::query()
            ->where('device_id', $deviceId)
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('fix_time', [$from, $to])
                    ->orWhereBetween('device_time', [$from, $to])
                    ->orWhereBetween('server_time', [$from, $to]);
            })
            ->orderByRaw('COALESCE(fix_time, device_time, server_time) asc')
            ->get()
            ->map(static function (Position $position): array {
                return [
                    'id' => $position->traccar_id ?? $position->getKey(),
                    'deviceId' => (int) $position->device_id,
                    'latitude' => (float) $position->latitude,
                    'longitude' => (float) $position->longitude,
                    'altitude' => $position->altitude !== null ? (float) $position->altitude : null,
                    'speed' => (float) ($position->speed ?? 0),
                    'course' => $position->course !== null ? (float) $position->course : null,
                    'address' => $position->address,
                    'attributes' => is_array($position->attributes) ? $position->attributes : [],
                    'fixTime' => $position->fix_time?->toIso8601String(),
                    'deviceTime' => $position->device_time?->toIso8601String(),
                    'serverTime' => $position->server_time?->toIso8601String(),
                ];
            })
            ->all();
    }
}
