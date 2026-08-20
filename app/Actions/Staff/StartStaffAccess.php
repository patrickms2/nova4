<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Domain\Staff\EvaluateAccessGrant;
use App\Enums\DomoticsEventType;
use App\Enums\WorkSessionStatus;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\DomoticsEvent;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\Domotics\DeviceAdapterInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ENTER. Server-authoritative: validates the grant, creates the WorkSession,
 * requests the gate to open and records an audit trail. The device only
 * requests this operation; it never decides authorization itself.
 */
final readonly class StartStaffAccess
{
    public function __construct(
        private EvaluateAccessGrant $evaluator,
        private DeviceAdapterInterface $gate,
    ) {}

    /**
     * @throws StaffAccessDeniedException
     */
    public function handle(User $staff, AccessGrant $grant, AccessPoint $accessPoint): WorkSession
    {
        return DB::transaction(function () use ($staff, $grant, $accessPoint): WorkSession {
            // Lock the grant row to make concurrent double-ENTER requests safe.
            $grant = AccessGrant::query()->whereKey($grant->id)->lockForUpdate()->firstOrFail();
            $grant->load('accessPoints', 'property');

            $decision = $this->evaluator->forStart($staff, $grant, $accessPoint);

            if (! $decision->authorized) {
                $this->recordDenied($grant, $accessPoint, $staff, $decision->reason ?? 'denied');

                throw new StaffAccessDeniedException($decision->reason ?? 'denied');
            }

            $session = WorkSession::create([
                'property_id' => $grant->property_id,
                'access_grant_id' => $grant->id,
                'access_point_id' => $accessPoint->id,
                'user_id' => $staff->id,
                'status' => WorkSessionStatus::Active->value,
                'started_at' => now(),
            ]);

            DomoticsEvent::create([
                'property_id' => $grant->property_id,
                'access_point_id' => $accessPoint->id,
                'access_grant_id' => $grant->id,
                'user_id' => $staff->id,
                'event_type' => DomoticsEventType::AccessGranted,
                'payload' => ['work_session_id' => $session->id, 'source' => 'staff_app'],
                'created_at' => now(),
            ]);

            $this->requestGateOpen($session, $accessPoint, $staff);

            return $session;
        });
    }

    private function recordDenied(AccessGrant $grant, AccessPoint $accessPoint, User $staff, string $reason): void
    {
        DomoticsEvent::create([
            'property_id' => $grant->property_id,
            'access_point_id' => $accessPoint->id,
            'access_grant_id' => $grant->id,
            'user_id' => $staff->id,
            'event_type' => DomoticsEventType::AccessDenied,
            'payload' => ['reason' => $reason, 'source' => 'staff_app'],
            'created_at' => now(),
        ]);
    }

    private function requestGateOpen(WorkSession $session, AccessPoint $accessPoint, User $staff): void
    {
        DomoticsEvent::create([
            'property_id' => $session->property_id,
            'access_point_id' => $accessPoint->id,
            'access_grant_id' => $session->access_grant_id,
            'user_id' => $staff->id,
            'event_type' => DomoticsEventType::GateOpenRequested,
            'payload' => ['work_session_id' => $session->id],
            'created_at' => now(),
        ]);

        try {
            $opened = $this->gate->open($accessPoint);
        } catch (\Throwable $exception) {
            Log::error('Staff Access: gate open failed', [
                'work_session_id' => $session->id,
                'error' => $exception->getMessage(),
            ]);
            $opened = false;
        }

        DomoticsEvent::create([
            'property_id' => $session->property_id,
            'access_point_id' => $accessPoint->id,
            'access_grant_id' => $session->access_grant_id,
            'user_id' => $staff->id,
            'event_type' => $opened ? DomoticsEventType::GateOpened : DomoticsEventType::GateFailed,
            'payload' => ['work_session_id' => $session->id],
            'created_at' => now(),
        ]);

        // The WorkSession remains valid (ENTRY_GRANTED) even if the gate
        // failed to physically open: the session is recoverable state, the
        // gate failure is visible via the audit trail for the manager.
    }
}
