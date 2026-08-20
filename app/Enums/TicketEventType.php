<?php

namespace App\Enums;

enum TicketEventType: string
{
    case StatusChanged   = 'status_changed';
    case Assigned        = 'assigned';
    case Unassigned      = 'unassigned';
    case PriorityChanged = 'priority_changed';
    case CategoryChanged = 'category_changed';
    case Closed          = 'closed';
    case Reopened        = 'reopened';
}
