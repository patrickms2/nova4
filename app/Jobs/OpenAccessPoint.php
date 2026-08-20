<?php

namespace App\Jobs;

use App\Enums\AccessPointType;
use App\Enums\DomoticsEventType;
use App\Models\AccessPoint;
use App\Models\DomoticsEvent;
use App\Models\User;
use App\Services\Domotics\DeviceAdapterInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OpenAccessPoint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public AccessPoint $accessPoint,
        public ?User $user = null,
        public ?int $accessGrantId = null,
    ) {
    }

    public function handle(DeviceAdapterInterface $adapter): void
    {
        $adapter->open($this->accessPoint);

        DomoticsEvent::create([
            'property_id' => $this->accessPoint->property_id,
            'device_id' => $this->accessPoint->device_id,
            'access_point_id' => $this->accessPoint->id,
            'access_grant_id' => $this->accessGrantId,
            'user_id' => $this->user?->id,
            'event_type' => $this->eventType(),
            'payload' => [
                'source' => $this->user ? 'manual' : 'pin',
            ],
            'created_at' => now(),
        ]);
    }

    protected function eventType(): DomoticsEventType
    {
        return $this->accessPoint->type === AccessPointType::Light
            ? DomoticsEventType::LightTurnedOn
            : DomoticsEventType::AccessGranted;
    }
}
