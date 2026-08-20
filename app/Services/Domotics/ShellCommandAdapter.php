<?php

namespace App\Services\Domotics;

use App\Models\AccessPoint;
use Illuminate\Support\Facades\Process;

class ShellCommandAdapter implements DeviceAdapterInterface
{
    public function open(AccessPoint $accessPoint): bool
    {
        return $this->run('open', $accessPoint);
    }

    public function close(AccessPoint $accessPoint): bool
    {
        return $this->run('close', $accessPoint);
    }

    public function status(AccessPoint $accessPoint): array
    {
        return ['adapter' => 'shell', 'ok' => true];
    }

    protected function run(string $action, AccessPoint $accessPoint): bool
    {
        $template = config("domotics.commands.{$action}");

        if (empty($template)) {
            return false;
        }

        $command = $this->interpolate($template, $accessPoint);

        return Process::run($command)->successful();
    }

    protected function interpolate(string $template, AccessPoint $accessPoint): string
    {
        return str_replace(
            ['{id}', '{name}', '{property_id}', '{device_id}'],
            [
                $accessPoint->id,
                escapeshellarg($accessPoint->name),
                $accessPoint->property_id,
                $accessPoint->device_id,
            ],
            $template
        );
    }
}
