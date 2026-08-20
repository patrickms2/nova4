<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\DomoticsEventType;
use App\Enums\WorkSessionStatus;
use App\Models\DomoticsEvent;
use App\Models\WorkSession;
use Illuminate\Support\Facades\DB;

/**
 * FINISH. Pressing finish does NOT immediately complete the session: it
 * enters the finishing/report process. If no report is required, the
 * session can be completed immediately by CompleteStaffAccess.
 */
final readonly class RequestStaffAccessFinish
{
    public function __construct(private CompleteStaffAccess $completeStaffAccess) {}

    public function handle(WorkSession $session): WorkSession
    {
        return DB::transaction(function () use ($session): WorkSession {
            $session = WorkSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            // Idempotent: a second FINISH tap on an already-finishing/finished
            // session must not create duplicate events or state transitions.
            if ($session->status !== WorkSessionStatus::Active) {
                return $session;
            }

            $session->update([
                'status' => $session->accessGrant->report_required
                    ? WorkSessionStatus::ReportPending->value
                    : WorkSessionStatus::Finishing->value,
                'finish_requested_at' => now(),
            ]);

            DomoticsEvent::create([
                'property_id' => $session->property_id,
                'access_point_id' => $session->access_point_id,
                'access_grant_id' => $session->access_grant_id,
                'user_id' => $session->user_id,
                'event_type' => DomoticsEventType::FinishRequested,
                'payload' => ['work_session_id' => $session->id],
                'created_at' => now(),
            ]);

            if (! $session->accessGrant->report_required) {
                return $this->completeStaffAccess->handle($session->fresh());
            }

            return $session;
        });
    }
}
