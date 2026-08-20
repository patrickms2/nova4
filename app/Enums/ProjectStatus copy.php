<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum ProjectStatus: string implements HasColor, HasIcon
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::InProgress => 'warning',
            self::Closed => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-folder-open',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Closed => 'heroicon-o-check-circle',
        };
    }
}
