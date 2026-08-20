<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions;

enum MissionState: string
{
    case Detected = 'Detected';
    case Planning = 'Planning';
    case WaitingApproval = 'Waiting Approval';
    case Running = 'Running';
    case Paused = 'Paused';
    case Completed = 'Completed';
    case Failed = 'Failed';
    case Cancelled = 'Cancelled';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled], true);
    }

    public function canAdvance(): bool
    {
        return ! $this->isTerminal()
            && ! in_array($this, [self::WaitingApproval, self::Paused], true);
    }
}
