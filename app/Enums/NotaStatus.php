<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotaStatus: string implements HasLabel, HasColor
{


    case Open = 'open';
    case Pending = 'pending';
    case Completed = 'completed';
    case Closed = 'closed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::Pending => 'Pendiente',
            self::Completed => 'Completado',
            self::Closed => 'Cerrado',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Open => Color::Gray,
            self::Pending => Color::Yellow,
            self::Completed => Color::Green,
            self::Closed => Color::Blue,
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public static function options(): array
    {
        return [
            self::Open->value => self::Open->getLabel(),
            self::Pending->value => self::Pending->getLabel(),
            self::Completed->value => self::Completed->getLabel(),
            self::Closed->value => self::Closed->getLabel(),
        ];
    }
}
