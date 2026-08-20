<?php

namespace App\Support;

use App\Models\CommunityAppointment;
use Carbon\CarbonImmutable;

class CommunityAppointmentAvailability
{
    /** @return array<string, string> */
    public function slots(?int $communityId, string $date): array
    {
        $day = CarbonImmutable::parse($date)->startOfDay();

        if ($day->isWeekend() || $day->lt(today())) {
            return [];
        }

        $booked = CommunityAppointment::query()
            ->when(
                $communityId,
                fn ($query) => $query->where('community_id', $communityId),
                fn ($query) => $query->whereNull('community_id'),
            )
            ->whereDate('starts_at', $day)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->pluck('starts_at')
            ->map(fn ($startsAt): string => CarbonImmutable::parse($startsAt)->format('H:i'))
            ->all();

        $slots = [];
        $cursor = $day->setTime(9, 0);
        $lastSlot = $day->setTime(16, 30);

        while ($cursor->lte($lastSlot)) {
            $time = $cursor->format('H:i');

            if (! in_array($time, $booked, true) && $cursor->isFuture()) {
                $slots[$time] = $time;
            }

            $cursor = $cursor->addMinutes(30);
        }

        return $slots;
    }

    public function isAvailable(?int $communityId, string $date, string $time): bool
    {
        return array_key_exists($time, $this->slots($communityId, $date));
    }
}
