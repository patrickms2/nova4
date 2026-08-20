<?php

namespace Tests\Feature\Staff;

use App\Actions\Staff\CompleteStaffAccess;
use App\Actions\Staff\RequestStaffAccessFinish;
use App\Actions\Staff\StaffAccessDeniedException;
use App\Actions\Staff\StartStaffAccess;
use App\Actions\Staff\SubmitWorkReport;
use App\Actions\Staff\WorkReportRejectedException;
use App\Domain\Staff\Contracts\WorkReportSummarizerContract;
use App\Domain\Staff\EvaluateAccessGrant;
use App\Enums\WorkSessionStatus;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\Property;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\Domotics\DeviceAdapterInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeGrant(array $overrides = []): AccessGrant
    {
        $property = $overrides['property'] ?? Property::factory()->create(['timezone' => 'Atlantic/Canary']);
        $staff = $overrides['staff'] ?? User::factory()->create();
        $accessPoint = AccessPoint::factory()->create(['property_id' => $property->id, 'device_id' => null]);

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

    private function startAction(): StartStaffAccess
    {
        return new StartStaffAccess(new EvaluateAccessGrant(), app(DeviceAdapterInterface::class));
    }

    public function test_valid_professional_can_start(): void
    {
        $grant = $this->makeGrant();
        $accessPoint = $grant->accessPoints->first();

        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);

        $this->assertSame(WorkSessionStatus::Active, $session->status);
        $this->assertDatabaseCount('work_sessions', 1);
    }

    public function test_outside_date_range_denied(): void
    {
        $grant = $this->makeGrant(['valid_until' => now('Atlantic/Canary')->subDay()]);
        $accessPoint = $grant->accessPoints->first();

        $this->expectException(StaffAccessDeniedException::class);
        $this->startAction()->handle($grant->user, $grant, $accessPoint);
    }

    public function test_wrong_weekday_denied(): void
    {
        $now = now('Atlantic/Canary');
        $wrongWeekday = $now->dayOfWeekIso === 1 ? 2 : 1;

        $grant = $this->makeGrant(['allowed_weekdays' => [$wrongWeekday]]);
        $accessPoint = $grant->accessPoints->first();

        $this->expectException(StaffAccessDeniedException::class);
        $this->startAction()->handle($grant->user, $grant, $accessPoint);
    }

    public function test_outside_allowed_time_denied(): void
    {
        $farFuture = now('Atlantic/Canary')->addHours(3)->format('H:i:s');

        $grant = $this->makeGrant([
            'allowed_time_from' => $farFuture,
            'allowed_time_until' => $farFuture,
        ]);
        $accessPoint = $grant->accessPoints->first();

        $this->expectException(StaffAccessDeniedException::class);
        $this->startAction()->handle($grant->user, $grant, $accessPoint);
    }

    public function test_revoked_grant_denied(): void
    {
        $grant = $this->makeGrant(['revoked_at' => now()]);
        $accessPoint = $grant->accessPoints->first();

        $this->expectException(StaffAccessDeniedException::class);
        $this->startAction()->handle($grant->user, $grant, $accessPoint);
    }

    public function test_second_active_session_prevented(): void
    {
        $grant = $this->makeGrant();
        $accessPoint = $grant->accessPoints->first();

        $this->startAction()->handle($grant->user, $grant, $accessPoint);

        $this->expectException(StaffAccessDeniedException::class);
        $this->startAction()->handle($grant->user, $grant->fresh(['accessPoints', 'property']), $accessPoint);
    }

    public function test_successful_start_records_audit_event(): void
    {
        config(['domotics.adapter' => 'dummy']);
        $grant = $this->makeGrant();
        $accessPoint = $grant->accessPoints->first();

        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);

        $this->assertDatabaseHas('domotics_events', [
            'access_grant_id' => $grant->id,
            'event_type' => 'access_granted',
        ]);
        $this->assertDatabaseHas('domotics_events', [
            'event_type' => 'gate_opened',
        ]);
        unset($session);
    }

    public function test_finish_enters_report_pending_state_when_required(): void
    {
        $grant = $this->makeGrant(['report_required' => true]);
        $accessPoint = $grant->accessPoints->first();
        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);

        $session = app(RequestStaffAccessFinish::class)->handle($session);

        $this->assertSame(WorkSessionStatus::ReportPending, $session->status);
    }

    public function test_finish_completes_immediately_when_report_not_required(): void
    {
        $grant = $this->makeGrant(['report_required' => false]);
        $accessPoint = $grant->accessPoints->first();
        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);

        $session = app(RequestStaffAccessFinish::class)->handle($session);

        $this->assertSame(WorkSessionStatus::Finished, $session->status);
        $this->assertNotNull($session->finished_at);
    }

    public function test_required_voice_enforced(): void
    {
        $grant = $this->makeGrant(['report_required' => true, 'voice_required' => true]);
        $accessPoint = $grant->accessPoints->first();
        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);
        $session = app(RequestStaffAccessFinish::class)->handle($session);

        $this->expectException(WorkReportRejectedException::class);
        app(SubmitWorkReport::class)->handle($session, null, []);
    }

    public function test_required_photo_enforced(): void
    {
        $grant = $this->makeGrant(['report_required' => true, 'photo_required' => true, 'minimum_photos' => 2]);
        $accessPoint = $grant->accessPoints->first();
        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);
        $session = app(RequestStaffAccessFinish::class)->handle($session);

        $this->expectException(WorkReportRejectedException::class);
        app(SubmitWorkReport::class)->handle($session, null, ['photo1.jpg']);
    }

    public function test_valid_report_accepted_and_completes_session(): void
    {
        $grant = $this->makeGrant(['report_required' => true, 'photo_required' => true, 'minimum_photos' => 1]);
        $accessPoint = $grant->accessPoints->first();
        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);
        $session = app(RequestStaffAccessFinish::class)->handle($session);

        $report = app(SubmitWorkReport::class)->handle($session, null, ['photo1.jpg']);

        $this->assertSame(['photo1.jpg'], $report->photos);

        $session->refresh();
        $this->assertSame(WorkSessionStatus::Finished, $session->status);
        $this->assertNotNull($session->finished_at);
    }

    public function test_session_completion_records_timestamps_and_duration(): void
    {
        $grant = $this->makeGrant();
        $accessPoint = $grant->accessPoints->first();
        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);

        $session = app(RequestStaffAccessFinish::class)->handle($session);

        $this->assertNotNull($session->started_at);
        $this->assertNotNull($session->finished_at);
        $this->assertGreaterThanOrEqual(0, $session->elapsedSeconds());
    }

    public function test_double_enter_is_safe(): void
    {
        $grant = $this->makeGrant();
        $accessPoint = $grant->accessPoints->first();

        $this->startAction()->handle($grant->user, $grant, $accessPoint);

        try {
            $this->startAction()->handle($grant->user, $grant->fresh(['accessPoints', 'property']), $accessPoint);
        } catch (StaffAccessDeniedException) {
            // expected
        }

        $this->assertDatabaseCount('work_sessions', 1);
    }

    public function test_double_complete_is_safe(): void
    {
        $grant = $this->makeGrant();
        $accessPoint = $grant->accessPoints->first();
        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);

        $first = app(CompleteStaffAccess::class)->handle($session);
        $second = app(CompleteStaffAccess::class)->handle($first);

        $this->assertSame($first->finished_at->timestamp, $second->finished_at->timestamp);
    }

    public function test_gate_failure_does_not_corrupt_session(): void
    {
        $this->app->bind(DeviceAdapterInterface::class, function () {
            return new class implements DeviceAdapterInterface {
                public function open(AccessPoint $accessPoint): bool
                {
                    return false;
                }

                public function close(AccessPoint $accessPoint): bool
                {
                    return false;
                }

                public function status(AccessPoint $accessPoint): array
                {
                    return ['status' => 'offline'];
                }
            };
        });

        $grant = $this->makeGrant();
        $accessPoint = $grant->accessPoints->first();

        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);

        $this->assertSame(WorkSessionStatus::Active, $session->status);
        $this->assertDatabaseHas('domotics_events', ['event_type' => 'gate_failed']);
    }

    public function test_ai_failure_does_not_destroy_original_report(): void
    {
        $this->app->bind(WorkReportSummarizerContract::class, function () {
            return new class implements WorkReportSummarizerContract {
                public function summarize(string $audioPath): array
                {
                    return ['success' => false, 'transcription' => null, 'summary' => null, 'error' => 'provider_down'];
                }
            };
        });

        $grant = $this->makeGrant(['report_required' => true, 'voice_required' => true]);
        $accessPoint = $grant->accessPoints->first();
        $session = $this->startAction()->handle($grant->user, $grant, $accessPoint);
        $session = app(RequestStaffAccessFinish::class)->handle($session);

        $report = app(SubmitWorkReport::class)->handle($session, '/tmp/fake-voice.ogg', []);

        $this->assertSame('/tmp/fake-voice.ogg', $report->voice_path);
        $this->assertNull($report->summary);
        $this->assertFalse($report->ai_metadata['success']);
    }
}
