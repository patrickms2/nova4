<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\DomoticsEventType;
use App\Enums\WorkSessionStatus;
use App\Models\DomoticsEvent;
use App\Models\WorkSession;
use App\Services\Domotics\DeviceAdapterInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Authorizes the normal exit, requests the gate to open, and marks the
 * WorkSession as finished. Does not physically "close" anything: the
 * normal sequence is open-for-exit -> professional leaves -> gate
 * closes/auto-closes on its own.
 */
final readonly class CompleteStaffAccess
{
    public function __construct(private DeviceAdapterInterface $gate) {}

    public function handle(WorkSession $session): WorkSession
    {
        return DB::transaction(function () use ($session): WorkSession {
            $session = WorkSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            // Idempotent: completing an already-finished session is a no-op.
            if ($session->status === WorkSessionStatus::Finished) {
                return $session;
            }

            $session->update(['status' => WorkSessionStatus::ExitAuthorized->value]);

            DomoticsEvent::create([
                'property_id' => $session->property_id,
                'access_point_id' => $session->access_point_id,
                'access_grant_id' => $session->access_grant_id,
                'user_id' => $session->user_id,
                'event_type' => DomoticsEventType::ExitGranted,
                'payload' => ['work_session_id' => $session->id],
                'created_at' => now(),
            ]);

            if ($session->accessPoint !== null) {
                try {
                    $opened = $this->gate->open($session->accessPoint);
                } catch (\Throwable $exception) {
                    Log::error('Staff Access: exit gate open failed', [
                        'work_session_id' => $session->id,
                        'error' => $exception->getMessage(),
                    ]);
                    $opened = false;
                }

                DomoticsEvent::create([
                    'property_id' => $session->property_id,
                    'access_point_id' => $session->access_point_id,
                    'access_grant_id' => $session->access_grant_id,
                    'user_id' => $session->user_id,
                    'event_type' => $opened ? DomoticsEventType::GateOpened : DomoticsEventType::GateFailed,
                    'payload' => ['work_session_id' => $session->id, 'reason' => 'exit'],
                    'created_at' => now(),
                ]);
            }

            $session->update([
                'status' => WorkSessionStatus::Finished->value,
                'finished_at' => now(),
            ]);

            DomoticsEvent::create([
                'property_id' => $session->property_id,
                'access_point_id' => $session->access_point_id,
                'access_grant_id' => $session->access_grant_id,
                'user_id' => $session->user_id,
                'event_type' => DomoticsEventType::SessionFinished,
                'payload' => ['work_session_id' => $session->id],
                'created_at' => now(),
            ]);

            // A gate failure on exit does not corrupt the session: it is
            // still marked FINISHED (work is done) but remains visible to
            // the manager via the GateFailed audit event for manual exit.
            return $session->fresh();
        });
    }
}
