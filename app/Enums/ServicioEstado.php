<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ServicioEstado: string implements HasLabel, HasColor, HasIcon
{
    case NOATENDIDO = 'No Atendido';
    case ERROR = 'Error';
    case CANCELADO = 'Cancelado';
    case TRAMITADO = 'Tramitado';
    case RESERVADO = 'Reservado';
    case SOLICITADO = 'Solicitado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NOATENDIDO => 'No Atendido',
            self::ERROR => 'Error',
            self::CANCELADO => 'Cancelado',
            self::TRAMITADO => 'Tramitado',
            self::RESERVADO => 'Reservado',
            self::SOLICITADO => 'Solicitado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NOATENDIDO => 'gray',
            self::ERROR => 'danger',
            self::CANCELADO => 'danger',
            self::TRAMITADO => 'success',
            self::RESERVADO => 'warning',
            self::SOLICITADO => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NOATENDIDO => 'heroicon-m-clock',
            self::ERROR => 'heroicon-m-exclamation-circle',
            self::CANCELADO => 'heroicon-m-exclamation-circle',
            self::TRAMITADO => 'heroicon-m-check-circle',
            self::RESERVADO => 'heroicon-m-calendar',
            self::SOLICITADO => 'heroicon-m-exclamation-circle',
        };
    }

    // Static methods for backward compatibility
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
