<?php

declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaTableViewMode: string
{
    case Table = 'table';
    case Kanban = 'kanban';
    case Tree = 'tree';
}
