<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Enums;

enum ConversationStatus: string
{
    case ACTIVE = 'active';
    case WAITING = 'waiting';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case TIMED_OUT = 'timed_out';
}
