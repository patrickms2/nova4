<?php

declare(strict_types=1);

namespace App\Domain\Staff;

use App\Enums\WorkSessionStatus;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Server-authoritative decision on whether a professional is allowed to
 * start a Staff Access work session right now. The mobile device NEVER
 * decides this; it only requests the decision.
 */
final readonly class EvaluateAccessGrant
{
    public function forStart(User $staff, AccessGrant $grant, AccessPoint $accessPoint): AccessEvaluationResult
    {
        if ($grant->user_id !== $staff->id) {
            return AccessEvaluationResult::denied('grant_not_owned_by_staff');
        }

        if ($grant->revoked_at !== null) {
            return AccessEvaluationResult::denied('grant_revoked');
        }

        if (! $grant->is_active) {
            return AccessEvaluationResult::denied('grant_inactive');
        }

        $now = $this->propertyNow($grant);

        if ($grant->valid_from !== null && $now->lt($grant->valid_from)) {
            return AccessEvaluationResult::denied('outside_date_range');
        }

        if ($grant->valid_until !== null && $now->gt($grant->valid_until)) {
            return AccessEvaluationResult::denied('outside_date_range');
        }

        if (! $grant->accessPoints->contains($accessPoint)) {
            return AccessEvaluationResult::denied('access_point_not_allowed');
        }

        if (! $grant->isWeekdayAllowed($now)) {
            return AccessEvaluationResult::denied('weekday_not_allowed');
        }

        if (! $grant->isTimeAllowed($now)) {
            return AccessEvaluationResult::denied('outside_allowed_time');
        }

        if ($grant->workSessions()->where('status', '!=', WorkSessionStatus::Finished->value)->exists()) {
            return AccessEvaluationResult::denied('active_session_exists');
        }

        return AccessEvaluationResult::authorized();
    }

    /**
     * Resolve "now" using the property's configured timezone, never the
     * device clock and never assuming server timezone == property timezone.
     */
    private function propertyNow(AccessGrant $grant): CarbonImmutable
    {
        $timezone = $grant->property?->timezone ?? config('app.timezone', 'UTC');

        return CarbonImmutable::now($timezone);
    }
}
