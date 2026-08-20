<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Domain\Staff\Contracts\WorkReportSummarizerContract;
use App\Enums\DomoticsEventType;
use App\Enums\WorkSessionStatus;
use App\Models\DomoticsEvent;
use App\Models\WorkReport;
use App\Models\WorkSession;
use Illuminate\Support\Facades\DB;

/**
 * Validates the daily work report against the AccessGrant's report policy,
 * persists both original evidence and AI-derived information, and moves the
 * session towards normal exit. Validation happens here in the domain layer,
 * not only in the mobile UI.
 */
final readonly class SubmitWorkReport
{
    public function __construct(
        private WorkReportSummarizerContract $summarizer,
        private CompleteStaffAccess $completeStaffAccess,
    ) {}

    /**
     * @param  array<int, string>  $photoPaths
     *
     * @throws WorkReportRejectedException
     */
    public function handle(WorkSession $session, ?string $voicePath, array $photoPaths): WorkReport
    {
        return DB::transaction(function () use ($session, $voicePath, $photoPaths): WorkReport {
            $session = WorkSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            $grant = $session->accessGrant;

            // Idempotent: submitting twice on an already-submitted session
            // returns the existing report instead of creating a duplicate.
            $existing = WorkReport::query()->where('work_session_id', $session->id)->first();

            if ($existing !== null) {
                return $existing;
            }

            if (! in_array($session->status, [WorkSessionStatus::ReportPending, WorkSessionStatus::Finishing], true)) {
                throw new WorkReportRejectedException('session_not_awaiting_report');
            }

            if ($grant->voice_required && $voicePath === null) {
                $this->reject($session, 'voice_required');
            }

            if ($grant->photo_required && count($photoPaths) < max(1, $grant->minimum_photos)) {
                $this->reject($session, 'photo_requirement_not_met');
            }

            $aiResult = $voicePath !== null
                ? $this->summarizer->summarize($voicePath)
                : ['success' => false, 'transcription' => null, 'summary' => null, 'error' => null];

            $report = WorkReport::create([
                'work_session_id' => $session->id,
                'voice_path' => $voicePath,
                'voice_transcription' => $aiResult['transcription'],
                'summary' => $aiResult['summary'],
                'photos' => $photoPaths,
                'ai_metadata' => [
                    'success' => $aiResult['success'],
                    'error' => $aiResult['error'],
                ],
                'submitted_at' => now(),
            ]);

            DomoticsEvent::create([
                'property_id' => $session->property_id,
                'access_point_id' => $session->access_point_id,
                'access_grant_id' => $session->access_grant_id,
                'user_id' => $session->user_id,
                'event_type' => DomoticsEventType::ReportSubmitted,
                'payload' => ['work_session_id' => $session->id, 'work_report_id' => $report->id],
                'created_at' => now(),
            ]);

            $this->completeStaffAccess->handle($session->fresh());

            return $report;
        });
    }

    private function reject(WorkSession $session, string $reason): never
    {
        DomoticsEvent::create([
            'property_id' => $session->property_id,
            'access_point_id' => $session->access_point_id,
            'access_grant_id' => $session->access_grant_id,
            'user_id' => $session->user_id,
            'event_type' => DomoticsEventType::ReportRejected,
            'payload' => ['work_session_id' => $session->id, 'reason' => $reason],
            'created_at' => now(),
        ]);

        throw new WorkReportRejectedException($reason);
    }
}
