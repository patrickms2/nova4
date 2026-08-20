<?php

namespace Tests\Feature;

use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\Automation;
use App\Models\Device;
use App\Models\DomoticsEvent;
use App\Models\RentalProperty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyDomoticsRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_property_can_have_domotics_relations(): void
    {
        $rentalProperty = RentalProperty::factory()->create();

        $device = Device::factory()->create(['rental_property_id' => $rentalProperty->id]);
        $accessPoint = AccessPoint::factory()->create(['rental_property_id' => $rentalProperty->id]);
        $accessGrant = AccessGrant::factory()->create(['rental_property_id' => $rentalProperty->id]);
        $automation = Automation::factory()->create([
            'rental_property_id' => $rentalProperty->id,
            'name' => 'Test automation',
        ]);
        $event = DomoticsEvent::factory()->create([
            'rental_property_id' => $rentalProperty->id,
            'event_type' => 'test',
            'payload' => [],
            'created_at' => now(),
        ]);

        $rentalProperty->refresh();

        $this->assertTrue($rentalProperty->devices->contains($device));
        $this->assertTrue($rentalProperty->accessPoints->contains($accessPoint));
        $this->assertTrue($rentalProperty->accessGrants->contains($accessGrant));
        $this->assertTrue($rentalProperty->automations->contains($automation));
        $this->assertTrue($rentalProperty->domoticsEvents->contains($event));

        $this->assertSame($rentalProperty->id, $device->rentalProperty->id);
        $this->assertSame($rentalProperty->id, $accessPoint->rentalProperty->id);
        $this->assertSame($rentalProperty->id, $accessGrant->rentalProperty->id);
        $this->assertSame($rentalProperty->id, $automation->rentalProperty->id);
        $this->assertSame($rentalProperty->id, $event->rentalProperty->id);
    }
}
