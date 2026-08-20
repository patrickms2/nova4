<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Mokhosh\FilamentKanban\Concerns\IsKanbanStatus;

enum UsuarioEstado: string implements HasLabel, HasColor, HasIcon
{
    use IsKanbanStatus;

    case BORRADOR = 'Borrador';
    case BAJA = 'Baja';
    case BLOQUEADO = 'Bloqueado';
    case ACTIVO = 'Activo';
    case PROBLEMAS = 'Problema';
    case PENDIENTE = 'Pendiente';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BORRADOR => 'Borrador',
            self::PENDIENTE => 'Pendiente',
            self::BAJA => 'Baja',
            self::BLOQUEADO => 'Bloqueado',
            self::ACTIVO => 'Activo',
            self::PROBLEMAS => 'Problema',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BORRADOR => 'gray',
            self::PENDIENTE => 'info',
            self::BAJA => 'danger',
            self::BLOQUEADO => 'danger',
            self::ACTIVO => 'success',
            self::PROBLEMAS => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::BORRADOR => 'heroicon-m-clock',
            self::PENDIENTE => 'heroicon-m-clock',
            self::BAJA => 'heroicon-m-archive-box',
            self::BLOQUEADO => 'heroicon-m-exclamation-circle',
            self::ACTIVO => 'heroicon-m-check-circle',
            self::PROBLEMAS => 'heroicon-m-exclamation-circle',
        };
    }

    // Static methods for backward compatibility
    public static function options(): array
    {
        return [
            self::BORRADOR->value => self::BORRADOR->getLabel(),
            self::PENDIENTE->value => self::PENDIENTE->getLabel(),
            self::BAJA->value => self::BAJA->getLabel(),
            self::BLOQUEADO->value => self::BLOQUEADO->getLabel(),
            self::ACTIVO->value => self::ACTIVO->getLabel(),
            self::PROBLEMAS->value => self::PROBLEMAS->getLabel(),
        ];
    }
}
