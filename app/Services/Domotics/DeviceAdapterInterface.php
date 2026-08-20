<?php

namespace App\Services\Domotics;

use App\Models\AccessPoint;

interface DeviceAdapterInterface
{
    public function open(AccessPoint $accessPoint): bool;

    public function close(AccessPoint $accessPoint): bool;

    public function status(AccessPoint $accessPoint): array;
}
