<?php

namespace Tests\Feature\Domotics;

use App\Enums\AccessPointType;
use App\Jobs\OpenAccessPoint;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\Device;
use App\Models\Property;
use App\Models\User;
use App\Services\Domotics\DeviceAdapterInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_owner_can_update_property(): void
    {
        $user = User::factory()->create();
        $property = Property::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('update', $property));
    }

    public function test_non_member_cannot_update_property(): void
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();

        $this->assertFalse($user->can('update', $property));
    }

    public function test_valid_pin_opens_access_point(): void
    {
        Bus::fake([OpenAccessPoint::class]);

        $property = Property::factory()->create();
        $accessPoint = AccessPoint::factory()->create(['property_id' => $property->id]);
        $accessGrant = AccessGrant::factory()->create(['property_id' => $property->id]);
        $accessGrant->accessPoints()->attach($accessPoint);

        $this->artisan('app:validate-access-pin', [
            'pin' => $accessGrant->pin,
            'accessPoint' => $accessPoint->id,
        ])->assertSuccessful();

        Bus::assertDispatched(OpenAccessPoint::class);
    }

    public function test_invalid_pin_denies_access(): void
    {
        $property = Property::factory()->create();
        $accessPoint = AccessPoint::factory()->create(['property_id' => $property->id]);

        $this->artisan('app:validate-access-pin', [
            'pin' => '9999',
            'accessPoint' => $accessPoint->id,
        ])->assertFailed();
    }

    public function test_property_member_can_open_access_point(): void
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        $property->users()->attach($user, ['role' => 'owner']);
        $accessPoint = AccessPoint::factory()->create(['property_id' => $property->id]);

        $this->assertTrue($user->can('open', $accessPoint));
    }

    public function test_open_access_point_job_creates_event(): void
    {
        $property = Property::factory()->create();
        $accessPoint = AccessPoint::factory()->create(['property_id' => $property->id]);

        OpenAccessPoint::dispatchSync($accessPoint);

        $this->assertDatabaseHas('domotics_events', [
            'access_point_id' => $accessPoint->id,
            'event_type' => 'access_granted',
        ]);
    }

    public function test_owner_can_access_domotics_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/domotics');

        $response->assertOk();
    }

    public function test_shell_adapter_executes_command(): void
    {
        Process::fake();

        $accessPoint = AccessPoint::factory()->create();
        config(['domotics.adapter' => 'shell']);
        config(['domotics.commands.open' => 'open-gate --id={id} --name={name}']);

        $result = app(DeviceAdapterInterface::class)->open($accessPoint);

        $this->assertTrue($result);
        Process::assertRan(fn ($process) => is_string($process->command) && str_contains($process->command, 'open-gate'));
    }

    public function test_ikea_home_adapter_turns_on_light(): void
    {
        Http::fake();

        $device = Device::factory()->create(['identifier' => 'ikea-light-123']);
        $accessPoint = AccessPoint::factory()->create([
            'type' => AccessPointType::Light->value,
            'device_id' => $device->id,
        ]);

        config(['domotics.adapter' => 'ikea']);
        config(['domotics.ikea.hub_ip' => '192.168.1.100']);
        config(['domotics.ikea.token' => 'test-token']);

        OpenAccessPoint::dispatchSync($accessPoint);

        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH'
                && str_contains($request->url(), 'https://192.168.1.100:8443/v1/devices/ikea-light-123')
                && $request->data()[0]['attributes']['isOn'] === true;
        });
    }
}
