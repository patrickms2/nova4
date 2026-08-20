<?php

declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaViewType: string
{
    case Table = 'table';
    case Kanban = 'kanban';
    case Calendar = 'calendar';
    case Roster = 'roster';
    case Tree = 'tree';
    case Cards = 'cards';
    case Timeline = 'timeline';
    case Map = 'map';
}
