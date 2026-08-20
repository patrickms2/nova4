<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum ProjectPriority: string implements HasColor, HasIcon
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function getColor(): string
    {
        return match ($this) {
            self::Low => 'success',
            self::Medium => 'info',
            self::High => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Low => 'heroicon-s-arrow-down',
            self::Medium => 'heroicon-s-minus',
            self::High => 'heroicon-s-arrow-up',
        };
    }
}
