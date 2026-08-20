<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;

enum TicketStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case abierto = 'abierto';
    case en_proceso = 'en_proceso';
    case resuelto = 'resuelto';
    case cerrado = 'cerrado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::abierto => 'Abierto',
            self::en_proceso => 'En Proceso',
            self::resuelto => 'Resuelto',
            self::cerrado => 'Cerrado',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::abierto => 'Abierto',
            self::en_proceso => 'En Proceso',
            self::resuelto => 'Resuelto',
            self::cerrado => 'Cerrado',
        };
    }

    public function getColor(): array|string|null
    {
        return match ($this) {
            self::abierto => 'warning',
            self::en_proceso => 'info',
            self::resuelto => 'success',
            self::cerrado => 'danger',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::abierto => 'heroicon-s-check-circle',
            self::en_proceso => 'heroicon-s-clock',
            self::resuelto => 'heroicon-s-check-circle',
            self::cerrado => 'heroicon-s-x-circle',
        };
    }

    public static function options(): array
    {
        return [
            self::abierto->value => self::abierto->getLabel(),
            self::en_proceso->value => self::en_proceso->getLabel(),
            self::resuelto->value => self::resuelto->getLabel(),
            self::cerrado->value => self::cerrado->getLabel(),
        ];
    }
}
