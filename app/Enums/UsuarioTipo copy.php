<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Mokhosh\FilamentKanban\Concerns\IsKanbanStatus;

enum UsuarioTipo: int implements HasLabel, HasColor, HasIcon
{
    use IsKanbanStatus;

    case EMPLEADO = 1;
    case HOTEL = 2;
    case ADMIN = 3;
    case TAXISTA = 4;
    case DEPARTAMENTO = 5;
    case CONDUCTOR = 7;
    case SUPERADMIN = 8;

    public function getTitle(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ADMIN => 'ADMIN',
            self::SUPERADMIN => 'SUPERADMIN',
            self::CONDUCTOR => 'CONDUCTOR',
            self::HOTEL => 'HOTEL',
            self::EMPLEADO => 'EMPLEADO',
            self::TAXISTA => 'TAXISTA',
            self::DEPARTAMENTO => 'DEPARTAMENTO',
        };
    }
    public function getIcon(): ?string
    {
        return match ($this) {
            self::ADMIN => 'heroicon-m-clock',
            self::SUPERADMIN => 'heroicon-m-exclamation-circle',
            self::CONDUCTOR => 'heroicon-m-exclamation-circle',
            self::HOTEL => 'heroicon-m-archive-box',
            self::EMPLEADO => 'heroicon-m-check-circle',
            self::TAXISTA => 'heroicon-m-exclamation-circle',
            self::DEPARTAMENTO => 'heroicon-m-check-circle',
        };
    }
    public function getColor(): string
    {
        return match ($this) {

            self::ADMIN => 'danger',
            self::SUPERADMIN => 'danger',
            self::CONDUCTOR => 'warning',
            self::HOTEL => 'warning',
            self::EMPLEADO => 'info',
            self::TAXISTA => 'warning',
            self::DEPARTAMENTO => 'success',
        };
    }
    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    // Static methods for backward compatibility
    public static function options(): array
    {
        return [
            self::ADMIN->value => self::ADMIN->getLabel(),
            self::SUPERADMIN->value => self::SUPERADMIN->getLabel(),
            self::CONDUCTOR->value => self::CONDUCTOR->getLabel(),
            self::HOTEL->value => self::HOTEL->getLabel(),
            self::EMPLEADO->value => self::EMPLEADO->getLabel(),
            self::TAXISTA->value => self::TAXISTA->getLabel(),
            self::DEPARTAMENTO->value => self::DEPARTAMENTO->getLabel(),
        ];
    }
}
