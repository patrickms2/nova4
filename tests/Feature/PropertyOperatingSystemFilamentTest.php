<?php

namespace Tests\Feature;

use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\Pages\ListAccessGrants;
use App\Filament\App\Rentals\Domotics\Resources\AccessPoints\Pages\ListAccessPoints;
use App\Filament\App\Rentals\Domotics\Resources\Devices\Pages\ListDevices;
use App\Filament\App\Rentals\Domotics\Resources\Properties\Pages\ViewProperty;
use App\Filament\App\Rentals\Resources\RentalReservationResource\Pages\ViewRentalReservation;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\Credential;
use App\Models\Person;
use App\Models\Property;
use App\Models\RentalProperty;
use App\Models\RentalReservation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PropertyOperatingSystemFilamentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reservation_table_and_calendar_routes_are_both_registered(): void
    {
        $this->assertTrue(app('router')->has('filament.app.rentals.resources.rental-reservations.index'));
        $this->assertTrue(app('router')->has('filament.app.rentals.resources.rental-reservations.calendar'));
        $this->assertSame('/app/rentals/rental-reservations', route('filament.app.rentals.resources.rental-reservations.index', absolute: false));
        $this->assertSame('/app/rentals/rental-reservations/calendar', route('filament.app.rentals.resources.rental-reservations.calendar', absolute: false));
    }

    public function test_core_property_and_access_pages_boot_with_canonical_relationships(): void
    {
        $user = User::factory()->create();
        $property = Property::factory()->create(['owner_id' => $user->id]);
        $profile = RentalProperty::factory()->create(['property_id' => $property->id]);
        $person = Person::factory()->create();
        $reservation = RentalReservation::factory()->create([
            'property_id' => $property->id,
            'rental_property_id' => $profile->id,
            'person_id' => $person->id,
        ]);
        $grant = AccessGrant::factory()->create([
            'property_id' => $property->id,
            'person_id' => $person->id,
            'source_type' => RentalReservation::class,
            'source_id' => $reservation->id,
        ]);
        $point = AccessPoint::factory()->create(['property_id' => $property->id]);
        $credential = Credential::factory()->create(['person_id' => $person->id]);
        $grant->accessPoints()->attach($point);
        $grant->credentials()->attach($credential);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        Livewire::test(ViewProperty::class, ['record' => $property->getRouteKey()])->assertSuccessful();
        Livewire::test(ViewRentalReservation::class, ['record' => $reservation->getRouteKey()])->assertSuccessful();
        Livewire::test(ListAccessGrants::class)->assertSuccessful();
        Livewire::test(ListAccessPoints::class)->assertSuccessful();
        Livewire::test(ListDevices::class)->assertSuccessful();
    }

    public function test_access_grant_list_masks_legacy_pin(): void
    {
        $user = User::factory()->create();
        $property = Property::factory()->create(['owner_id' => $user->id]);
        AccessGrant::factory()->create(['property_id' => $property->id, 'pin' => '739184']);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        Livewire::test(ListAccessGrants::class)
            ->assertDontSee('739184')
            ->assertSee('••••84');
    }

    public function test_close_action_uses_same_physical_authorization_boundary_as_open(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $point = AccessPoint::factory()->create(['property_id' => $property->id]);

        $this->assertTrue($owner->can('open', $point));
        $this->assertTrue($owner->can('close', $point));
        $this->assertFalse($outsider->can('open', $point));
        $this->assertFalse($outsider->can('close', $point));
    }
}
