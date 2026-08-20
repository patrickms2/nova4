<?php

namespace App\Services;

use App\Events\GpsEvent;
use App\Models\Taxi\Device;
use App\Models\Taxi\Usuario;
use Illuminate\Support\Facades\Cache;

class GpsTrackingService
{
    private const MIN_EMIT_INTERVAL_SECONDS = 2;
    private const HEARTBEAT_INTERVAL_SECONDS = 30;
    private const MIN_MOVEMENT_METERS = 12.0;
    private const MAX_REASONABLE_SPEED_MS = 70.0;
    private const MAX_REASONABLE_ACCURACY_METERS = 250.0;

    public function ingest(array $payload, ?string $clientIp = null, ?string $userAgent = null): ?array
    {
        $usuarioId = (int) ($payload['usuario_id'] ?? 0);
        $usuario = Usuario::query()->find($usuarioId);

        if (! $usuario) {
            \Log::warning('GpsTrackingService: usuario not found for tracking point', [
                'usuario_id' => $usuarioId,
            ]);
            return null;
        }

        $latitude = (float) ($payload['latitude'] ?? 0);
        $longitude = (float) ($payload['longitude'] ?? 0);
        $speedMs = max(0, (float) ($payload['speed_ms'] ?? 0));
        $accuracy = isset($payload['accuracy']) ? (float) $payload['accuracy'] : null;

        // Hard filter for obviously invalid telemetry.
        if ($speedMs > self::MAX_REASONABLE_SPEED_MS) {
            \Log::warning('GpsTrackingService: skipped by speed filter', [
                'usuario_id' => $usuarioId,
                'speed_ms' => $speedMs,
            ]);
            return null;
        }

        if ($accuracy !== null && $accuracy > self::MAX_REASONABLE_ACCURACY_METERS) {
            \Log::warning('GpsTrackingService: skipped by accuracy filter', [
                'usuario_id' => $usuarioId,
                'accuracy' => $accuracy,
            ]);
            return null;
        }

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            \Log::warning('GpsTrackingService: skipped by invalid coordinates', [
                'usuario_id' => $usuarioId,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
            return null;
        }

        $requestedTraccarDeviceId = (int) ($payload['traccar_device_id'] ?? 0);
        $deviceId = $requestedTraccarDeviceId > 0
            ? $requestedTraccarDeviceId
            : $this->resolveTraccarDeviceId($usuarioId);

        if (! $deviceId) {
            \Log::warning('GpsTrackingService: no Traccar device resolved', [
                'usuario_id' => $usuarioId,
                'requested_traccar_device_id' => $requestedTraccarDeviceId,
            ]);
        }

        if (! $this->shouldEmitTrackingPoint($usuarioId, $latitude, $longitude, $speedMs)) {
            \Log::info('GpsTrackingService: skipped by anti-jitter filter', [
                'usuario_id' => $usuarioId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'speed_ms' => $speedMs,
            ]);
            return null;
        }

        $speedKmh = round($speedMs * 3.6, 2);

        $location = [
            'vehicle_id' => $usuario->id,
            'usuario_id' => $usuario->id,
            'device_id' => $deviceId,
            'nombre' => $usuario->nombre ?? 'Unknown Vehicle',
            'manufacturer_brand' => 'TAXI',
            'vehicle_model' => $usuario->nombre ?? 'Unknown',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'speed' => $speedKmh,
            'speed_ms' => $speedMs,
            'heading' => (float) ($payload['heading'] ?? 0),
            'accuracy' => $accuracy,
            'altitude' => isset($payload['altitude']) ? (float) $payload['altitude'] : null,
            'timestamp' => now()->toIso8601String(),
            'client_ip' => $clientIp,
            'user_agent' => $userAgent,
        ];

        $this->forwardToTraccar($usuarioId, $location, $deviceId);

        broadcast(new GpsEvent($location))->toOthers();

        \Log::info('GpsTrackingService: tracking point emitted', [
            'usuario_id' => $usuarioId,
            'traccar_device_id' => $deviceId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'speed_ms' => $speedMs,
        ]);

        return $location;
    }

    private function forwardToTraccar(int $usuarioId, array $location, ?int $deviceId = null): void
    {
        try {
            if (! $deviceId) {
                return;
            }

            $traccarService = app(TraccarService::class);
            $traccarService->newRealtimePosition([
                'device_id' => $deviceId,
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'altitude' => $location['altitude'] ?? 0,
                // Traccar espera knots.
                'speed' => round(((float) $location['speed_ms']) * 1.94384449, 3),
                'course' => $location['heading'] ?? 0,
                'address' => 'Browser movement tracking',
                'attributes' => [
                    'source' => 'browser_movement_websocket',
                    'ip' => $location['client_ip'] ?? null,
                    'accuracy' => $location['accuracy'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::warning('GpsTrackingService: forwardToTraccar failed', [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function shouldEmitTrackingPoint(int $usuarioId, float $lat, float $lng, float $speedMs): bool
    {
        $stateKey = "gps:tracking:last:{$usuarioId}";
        $now = now();
        $last = Cache::get($stateKey);

        if (! is_array($last)) {
            Cache::put($stateKey, [
                'lat' => $lat,
                'lng' => $lng,
                'at' => $now->timestamp,
            ], now()->addHours(8));
            return true;
        }

        $secondsSinceLast = max(0, $now->timestamp - ((int) ($last['at'] ?? 0)));
        if ($secondsSinceLast < self::MIN_EMIT_INTERVAL_SECONDS) {
            return false;
        }

        $distance = $this->haversineMeters(
            (float) $last['lat'],
            (float) $last['lng'],
            $lat,
            $lng
        );
        $isMoving = $speedMs >= 0.7;
        $isHeartbeatDue = $secondsSinceLast >= self::HEARTBEAT_INTERVAL_SECONDS;

        $shouldEmit = $distance >= self::MIN_MOVEMENT_METERS || $isMoving || $isHeartbeatDue;
        if (! $shouldEmit) {
            return false;
        }

        Cache::put($stateKey, [
            'lat' => $lat,
            'lng' => $lng,
            'at' => $now->timestamp,
        ], now()->addHours(8));

        return true;
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $toRad = static fn (float $value): float => deg2rad($value);

        $dLat = $toRad($lat2 - $lat1);
        $dLng = $toRad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos($toRad($lat1)) * cos($toRad($lat2)) * sin($dLng / 2) ** 2;

        return 6371000 * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function resolveTraccarDeviceId(int $usuarioId): ?int
    {
        $localDevice = Device::query()
            ->where('usuario_id', $usuarioId)
            ->orWhere('unique_id', (string) $usuarioId)
            ->first();

        if ($localDevice?->traccar_id) {
            return (int) $localDevice->traccar_id;
        }

        try {
            $traccarService = app(TraccarService::class);
            $remoteDevices = $traccarService->getDevices();

            foreach ($remoteDevices as $device) {
                if (
                    isset($device['id'], $device['uniqueId']) &&
                    (string) $device['uniqueId'] === (string) $usuarioId
                ) {
                    return (int) $device['id'];
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('GpsTrackingService: resolveTraccarDeviceId remote lookup failed', [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
