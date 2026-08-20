<?php

namespace Tests\Feature\Staff;

use App\Enums\WorkSessionStatus;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\Property;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffMobileApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeGrant(array $overrides = []): AccessGrant
    {
        $property = $overrides['property'] ?? Property::factory()->create(['timezone' => 'Atlantic/Canary']);
        $staff = $overrides['staff'] ?? User::factory()->create();
        $accessPoint = AccessPoint::factory()->create([
            'property_id' => $property->id,
            'device_id' => null,
        ]);

        $grant = AccessGrant::factory()->create(array_merge([
            'property_id' => $property->id,
            'user_id' => $staff->id,
            'is_active' => true,
            'revoked_at' => null,
            'valid_from' => null,
            'valid_until' => null,
            'allowed_weekdays' => null,
            'allowed_time_from' => null,
            'allowed_time_until' => null,
            'report_required' => false,
            'voice_required' => false,
            'photo_required' => false,
            'minimum_photos' => 0,
        ], array_diff_key($overrides, array_flip(['property', 'staff']))));

        $grant->accessPoints()->attach($accessPoint);
        $grant->setRelation('property', $property);

        return $grant->fresh(['accessPoints', 'property']);
    }

    public function test_staff_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson(route('api.staff.login'), [
            'email' => 'staff@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson(route('api.staff.login'), [
            'email' => 'staff@example.com',
            'password' => 'wrong-password',
            'device_name' => 'Test Device',
        ]);

        $response->assertUnprocessable();
    }

    public function test_authenticated_staff_can_list_grants(): void
    {
        $grant = $this->makeGrant();
        Sanctum::actingAs($grant->user);

        $response = $this->getJson(route('api.staff.grants'));

        $response->assertOk()
            ->assertJsonCount(1, 'grants')
            ->assertJsonPath('grants.0.id', $grant->id);
    }

    public function test_authenticated_staff_can_start_session(): void
    {
        config(['domotics.adapter' => 'dummy']);
        $grant = $this->makeGrant();
        $accessPoint = $grant->accessPoints->first();
        Sanctum::actingAs($grant->user);

        $response = $this->postJson(route('api.staff.sessions.start'), [
            'access_grant_id' => $grant->id,
            'access_point_id' => $accessPoint->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('session.status', WorkSessionStatus::Active->value);
        $this->assertDatabaseCount('work_sessions', 1);
    }

    public function test_start_session_is_server_authoritative(): void
    {
        $grant = $this->makeGrant(['is_active' => false]);
        $accessPoint = $grant->accessPoints->first();
        Sanctum::actingAs($grant->user);

        $response = $this->postJson(route('api.staff.sessions.start'), [
            'access_grant_id' => $grant->id,
            'access_point_id' => $accessPoint->id,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('success', false);
        $this->assertDatabaseCount('work_sessions', 0);
    }

    public function test_authenticated_staff_can_finish_session(): void
    {
        config(['domotics.adapter' => 'dummy']);
        $grant = $this->makeGrant();
        $accessPoint = $grant->accessPoints->first();
        $session = WorkSession::create([
            'property_id' => $grant->property_id,
            'access_grant_id' => $grant->id,
            'access_point_id' => $accessPoint->id,
            'user_id' => $grant->user_id,
            'status' => WorkSessionStatus::Active->value,
            'started_at' => now(),
        ]);
        Sanctum::actingAs($grant->user);

        $response = $this->postJson(route('api.staff.sessions.finish', $session));

        $response->assertOk()
            ->assertJsonPath('session.status', WorkSessionStatus::Finished->value);
    }

    public function test_authenticated_staff_can_submit_report(): void
    {
        Storage::fake('public');
        config(['domotics.adapter' => 'dummy']);
        $grant = $this->makeGrant([
            'report_required' => true,
            'photo_required' => true,
            'minimum_photos' => 1,
        ]);
        $accessPoint = $grant->accessPoints->first();
        $session = WorkSession::create([
            'property_id' => $grant->property_id,
            'access_grant_id' => $grant->id,
            'access_point_id' => $accessPoint->id,
            'user_id' => $grant->user_id,
            'status' => WorkSessionStatus::ReportPending->value,
            'started_at' => now(),
        ]);
        Sanctum::actingAs($grant->user);

        $photo = UploadedFile::fake()->image('report.jpg');

        $response = $this->postJson(route('api.staff.sessions.report', $session), [
            'photos' => [$photo],
        ]);

        $response->assertOk()
            ->assertJsonPath('session.status', WorkSessionStatus::Finished->value)
            ->assertJsonPath('report.photos', fn ($photos) => count($photos) === 1);
        $this->assertDatabaseCount('work_reports', 1);
    }

    public function test_staff_cannot_access_other_users_session(): void
    {
        $grant = $this->makeGrant();
        $otherUser = User::factory()->create();
        $accessPoint = $grant->accessPoints->first();
        $session = WorkSession::create([
            'property_id' => $grant->property_id,
            'access_grant_id' => $grant->id,
            'access_point_id' => $accessPoint->id,
            'user_id' => $grant->user_id,
            'status' => WorkSessionStatus::Active->value,
            'started_at' => now(),
        ]);
        Sanctum::actingAs($otherUser);

        $response = $this->getJson(route('api.staff.sessions.show', $session));

        $response->assertForbidden();
    }
}
