<?php

namespace Tests\Feature\Domain;

use App\Filament\App\Rentals\Pages\CasaElPatioDashboard;
use App\Jobs\OpenAccessPoint;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\Credential;
use App\Models\Device;
use App\Models\Person;
use App\Models\Property;
use App\Models\RentalProperty;
use App\Models\RentalReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CanonicalIdentityAccessTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     */
    public function test_person_can_have_multiple_roles_without_user(): void
    {
        $person = Person::factory()->create(['user_id' => null]);
        $person->roles()->createMany([['role' => 'owner'], ['role' => 'professional']]);

        $this->assertNull($person->user);
        $this->assertEqualsCanonicalizing(['owner', 'professional'], $person->roles->pluck('role')->all());
    }

    public function test_user_can_link_to_person(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($user->person->is($person));
    }

    public function test_property_is_canonical_for_rentals_devices_and_access_points(): void
    {
        $property = Property::factory()->create();
        $profile = RentalProperty::factory()->create(['property_id' => $property->id]);
        $device = Device::factory()->create(['property_id' => $property->id, 'rental_property_id' => $profile->id]);
        $point = AccessPoint::factory()->create(['property_id' => $property->id, 'rental_property_id' => $profile->id, 'device_id' => $device->id]);

        $this->assertTrue($profile->property->is($property));
        $this->assertTrue($device->property->is($property));
        $this->assertTrue($point->property->is($property));
    }

    public function test_reservation_and_access_grant_use_canonical_subject_and_property(): void
    {
        $property = Property::factory()->create();
        $profile = RentalProperty::factory()->create(['property_id' => $property->id]);
        $person = Person::factory()->create();
        $reservation = RentalReservation::factory()->create(['property_id' => $property->id, 'rental_property_id' => $profile->id, 'person_id' => $person->id]);
        $credential = Credential::factory()->create(['person_id' => $person->id]);
        $grant = AccessGrant::factory()->create(['property_id' => $property->id, 'person_id' => $person->id, 'valid_from' => now()->subMinute(), 'valid_until' => now()->addMinute()]);
        $point = AccessPoint::factory()->create(['property_id' => $property->id]);
        $grant->credentials()->attach($credential);
        $grant->accessPoints()->attach($point);

        $this->assertTrue($reservation->property->is($property));
        $this->assertTrue($grant->person->is($person));
        $this->assertTrue($grant->credentials->contains($credential));
        $this->assertTrue($grant->isValidForAccessPoint($point));
    }

    public function test_expired_grant_and_credential_are_not_valid(): void
    {
        $grant = AccessGrant::factory()->create(['valid_until' => now()->subMinute()]);
        $point = AccessPoint::factory()->create(['property_id' => $grant->property_id]);
        $grant->accessPoints()->attach($point);
        $credential = Credential::factory()->create(['valid_until' => now()->subMinute()]);

        $this->assertFalse($grant->isValidForAccessPoint($point));
        $this->assertFalse($credential->isValidAt());
        $this->assertSame(0, AccessGrant::active()->whereKey($grant)->count());
    }

    public function test_credential_secret_is_encrypted_and_hidden(): void
    {
        $credential = Credential::factory()->create(['secret' => '583921']);
        $stored = DB::table('credentials')->where('id', $credential->id)->value('secret');

        $this->assertNotSame('583921', $stored);
        $this->assertSame('583921', $credential->secret);
        $this->assertArrayNotHasKey('secret', $credential->toArray());
        $this->assertSame('••••21', $credential->maskedValue());
    }

    public function test_legacy_and_first_class_pin_credentials_remain_functional(): void
    {
        Bus::fake([OpenAccessPoint::class]);
        $property = Property::factory()->create();
        $point = AccessPoint::factory()->create(['property_id' => $property->id]);
        $legacyGrant = AccessGrant::factory()->create(['property_id' => $property->id, 'pin' => '1234']);
        $legacyGrant->accessPoints()->attach($point);

        $this->artisan('app:validate-access-pin', ['pin' => '1234', 'accessPoint' => $point->id])->assertSuccessful();

        $credentialGrant = AccessGrant::factory()->create(['property_id' => $property->id, 'pin' => null]);
        $credentialGrant->accessPoints()->attach($point);
        $credential = Credential::factory()->create(['secret' => '583921']);
        $credentialGrant->credentials()->attach($credential);

        $this->artisan('app:validate-access-pin', ['pin' => '583921', 'accessPoint' => $point->id])->assertSuccessful();
        Bus::assertDispatchedTimes(OpenAccessPoint::class, 2);
    }

    public function test_physical_open_action_remains_policy_protected(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $point = AccessPoint::factory()->create(['property_id' => $property->id]);

        $this->assertTrue($owner->can('open', $point));
        $this->assertFalse($outsider->can('open', $point));
    }

    public function test_rentals_dashboard_uses_a_bounded_number_of_queries(): void
    {
        $property = Property::query()->where('slug', 'casa-el-patio')->first()
            ?? Property::factory()->create(['name' => 'Casa El Patio', 'slug' => 'casa-el-patio']);
        $profile = RentalProperty::query()->where('property_id', $property->id)->first()
            ?? RentalProperty::factory()->create(['property_id' => $property->id]);
        RentalReservation::factory()->count(8)->create(['property_id' => $property->id, 'rental_property_id' => $profile->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(CasaElPatioDashboard::class)->mount();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(40, $queryCount);
    }
}
