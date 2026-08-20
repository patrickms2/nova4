<?php

namespace App\Services\Domotics;

use App\Models\AccessPoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IkeaHomeAdapter implements DeviceAdapterInterface
{
    public function open(AccessPoint $accessPoint): bool
    {
        return $this->sendLightState($accessPoint, true);
    }

    public function close(AccessPoint $accessPoint): bool
    {
        return $this->sendLightState($accessPoint, false);
    }

    public function status(AccessPoint $accessPoint): array
    {
        $deviceId = $this->deviceId($accessPoint);

        if ($deviceId === null) {
            return ['ok' => false, 'error' => 'missing device identifier'];
        }

        try {
            $response = $this->http()
                ->get($this->url("/devices/{$deviceId}"));

            return $response->successful()
                ? ['ok' => true, 'data' => $response->json()]
                : ['ok' => false, 'error' => 'failed to fetch status'];
        } catch (\Throwable $e) {
            Log::error('IKEA Home status failed', ['exception' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    protected function sendLightState(AccessPoint $accessPoint, bool $isOn): bool
    {
        $deviceId = $this->deviceId($accessPoint);

        if ($deviceId === null) {
            return false;
        }

        try {
            $response = $this->http()
                ->patch($this->url("/devices/{$deviceId}"), [
                    [
                        'attributes' => [
                            'isOn' => $isOn,
                        ],
                    ],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('IKEA Home light control failed', ['exception' => $e->getMessage()]);

            return false;
        }
    }

    protected function deviceId(AccessPoint $accessPoint): ?string
    {
        $deviceId = $accessPoint->device?->identifier;

        if (empty($deviceId)) {
            Log::warning('IKEA Home adapter missing device identifier', ['access_point_id' => $accessPoint->id]);

            return null;
        }

        return $deviceId;
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withOptions([
            'verify' => config('domotics.ikea.verify_ssl', false),
        ])
            ->withToken(config('domotics.ikea.token'))
            ->acceptJson()
            ->asJson();
    }

    protected function url(string $path): string
    {
        $hubIp = config('domotics.ikea.hub_ip');

        return "https://{$hubIp}:8443/v1{$path}";
    }
}
