<?php

declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaInfolistLayout: string
{
    case Cards = 'cards';
    case Sections = 'sections';
    case Grid = 'grid';
    case Compact = 'compact';
}
