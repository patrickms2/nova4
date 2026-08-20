<?php

namespace App\Services\Domotics;

use App\Models\AccessPoint;

class DummyAdapter implements DeviceAdapterInterface
{
    public function open(AccessPoint $accessPoint): bool
    {
        return true;
    }

    public function close(AccessPoint $accessPoint): bool
    {
        return true;
    }

    public function status(AccessPoint $accessPoint): array
    {
        return ['status' => 'ok'];
    }
}
