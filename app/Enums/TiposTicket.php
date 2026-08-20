<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasDescription;
use Mokhosh\FilamentKanban\Concerns\IsKanbanStatus;

enum TiposTicket: string implements HasLabel, HasColor, HasIcon, HasDescription
{
    use IsKanbanStatus;

    case consulta = 'consulta';
    case gestion = 'gestion';
    case documento = 'documento';
    case error = 'error';
    case mejora = 'sugerencia';
    case cambio = 'cambio';
    case otro = 'otro';

    public function getTitle(): string
    {
        return $this->name;
    }
    public function getValue(): ?string
    {
        return match ($this) {
            self::consulta => 'consulta',
            self::gestion => 'gestion',
            self::documento => 'documento',
            self::error => 'error',
            self::mejora => 'mejora',
            self::cambio => 'cambio',
            self::otro => 'otro',
        };
    }
    public function getLabel(): ?string
    {
        return match ($this) {
            self::consulta=> 'Consulta información',
             self::gestion => 'Hacer Gestión',
             self::documento => 'Petición Documentación',
             self::error => 'Corregir error',
             self::mejora => 'Sugerencia',
             self::cambio => 'Cambio',
             self::otro => 'Otro',
        };
    }
    public function getIcon(): ?string
    {
        return match ($this) {
            self::consulta=> 'heroicon-m-clock',
            self::gestion => 'heroicon-m-exclamation-circle',
            self::documento => 'heroicon-m-archive-box',
            self::error => 'heroicon-m-exclamation-circle',
            self::mejora => 'heroicon-m-check-circle',
            self::cambio => 'heroicon-m-calendar',
            self::otro => 'heroicon-m-exclamation-circle',
        };
    }
    public function getColor(): string
    {
        return match ($this) {
            self::consulta=> 'info',
            self::gestion => 'warning',
            self::documento => 'success',
            self::error => 'danger',
            self::mejora => 'success',
            self::cambio => 'warning',
            self::otro => 'gray',
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
            self::consulta->getValue() => self::consulta->getLabel(),
            self::gestion->getValue() => self::gestion->getLabel(),
            self::documento->getValue() => self::documento->getLabel(),
            self::error->getValue() => self::error->getLabel(),
            self::mejora->getValue() => self::mejora->getLabel(),
            self::cambio->getValue() => self::cambio->getLabel(),
            self::otro->getValue() => self::otro->getLabel(),
        ];
    }
    public static function style(): array
    {
        return [
            self::consulta->getValue() => 'info',
            self::gestion->getValue() => 'warning',
            self::documento->getValue() => 'success',
            self::error->getValue() => 'danger',
            self::mejora->getValue() => 'success',
            self::cambio->getValue() => 'warning',
            self::otro->getValue() => 'gray',

        ];
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::consulta=> 'Consulta información',
            self::gestion => 'Hacer Gestión',
            self::documento => 'Petición Documentación',
            self::error => 'Corregir error',
            self::mejora => 'Sugerencia',
            self::cambio => 'Cambio',
            self::otro => 'Otro',
        };
        }
}
