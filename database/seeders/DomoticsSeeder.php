<?php

namespace Database\Seeders;

use App\Enums\AccessPointType;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\Device;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DomoticsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'domotics@example.com'],
            [
                'name' => 'Demo Owner',
                'password' => bcrypt('password'),
            ]
        );

        $property = Property::firstOrCreate(
            ['slug' => 'villa-demo'],
            [
                'name' => 'Villa Demo',
                'owner_id' => $owner->id,
                'timezone' => 'Atlantic/Canary',
            ]
        );

        if (! $property->users()->where('user_id', $owner->id)->exists()) {
            $property->users()->attach($owner, ['role' => 'owner']);
        }

        $device = Device::firstOrCreate(
            ['identifier' => 'demo-relay-001'],
            [
                'property_id' => $property->id,
                'name' => 'Controlador principal',
                'type' => DeviceType::Hub->value,
                'status' => DeviceStatus::Online->value,
            ]
        );

        $accessPoint = AccessPoint::firstOrCreate(
            ['property_id' => $property->id, 'name' => 'Portón principal'],
            [
                'device_id' => $device->id,
                'type' => AccessPointType::Gate->value,
            ]
        );

        $accessGrant = AccessGrant::firstOrCreate(
            ['property_id' => $property->id, 'pin' => '1234'],
            [
                'user_id' => $owner->id,
                'name' => 'Invitados demo',
            ]
        );

        if (! $accessGrant->accessPoints()->where('access_point_id', $accessPoint->id)->exists()) {
            $accessGrant->accessPoints()->attach($accessPoint);
        }
    }
}
