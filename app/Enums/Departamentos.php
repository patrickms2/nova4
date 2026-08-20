<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Departamentos: int implements HasLabel, HasColor, HasIcon
{

    case LABORAL ='LABORAL';
    case FISCAL = 'FISCAL';
    case CONTABLE = 'CONTABLE';
    case JURIDICO = 'JURIDICO';
    case TAXIS = 'TAXIS';
    case ADMINISTRACION = 'ADMINISTRACION';
    case DESARROLLO = 'DESARROLLO';
    case ATENCIONCLIENTE = 'ATENCIONCLIENTE';

    public function getValue(): string
    {
        return match ($this) {
            self::LABORAL => 'LABORAL',
            self::FISCAL => 'FISCAL',
            self::CONTABLE => 'CONTABLE',
            self::JURIDICO => 'JURIDICO',
            self::TAXIS => 'TAXIS',
            self::ADMINISTRACION => 'ADMINISTRACION',
            self::DESARROLLO => 'DESARROLLO',
            self::ATENCIONCLIENTE => 'ATENCIONCLIENTE',
        };
    }
    public function getTitle(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LABORAL => 'LABORAL',
            self::FISCAL => 'FISCAL',
            self::CONTABLE => 'CONTABLE',
            self::JURIDICO => 'JURIDICO',
            self::TAXIS => 'TAXIS',
            self::ADMINISTRACION => 'ADMINISTRACION',
            self::DESARROLLO => 'DESARROLLO',
            self::ATENCIONCLIENTE => 'ATENCIONCLIENTE',
        };
    }
    public function getIcon(): ?string
    {
        return match ($this) {
            self::LABORAL => 'heroicon-m-clock',
            self::FISCAL => 'heroicon-m-exclamation-circle',
            self::CONTABLE => 'heroicon-m-exclamation-circle',
            self::JURIDICO => 'heroicon-m-archive-box',
            self::TAXIS => 'heroicon-m-check-circle',
            self::ADMINISTRACION => 'heroicon-m-check-circle',
            self::DESARROLLO => 'heroicon-m-check-circle',
            self::ATENCIONCLIENTE => 'heroicon-m-check-circle',
        };
    }
    public function getColor(): string
    {
        return match ($this) {

            self::LABORAL => 'warning',
            self::FISCAL => 'warning',
            self::CONTABLE => 'warning',
            self::JURIDICO => 'warning',
            self::TAXIS => 'warning',
            self::ADMINISTRACION => 'success',
            self::DESARROLLO => 'success',
            self::ATENCIONCLIENTE => 'success',

        };
    }
    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->getValue() => $case->getLabel()])
            ->toArray();
    }

    // Static methods for backward compatibility
    public static function options(): array
    {
        return [
            self::LABORAL->getValue() => self::LABORAL->getLabel(),
            self::FISCAL->getValue() => self::FISCAL->getLabel(),
            self::CONTABLE->getValue() => self::CONTABLE->getLabel(),
            self::JURIDICO->getValue() => self::JURIDICO->getLabel(),
            self::TAXIS->getValue() => self::TAXIS->getLabel(),
            self::ADMINISTRACION->getValue() => self::ADMINISTRACION->getLabel(),
            self::DESARROLLO->getValue() => self::DESARROLLO->getLabel(),
            self::ATENCIONCLIENTE->getValue() => self::ATENCIONCLIENTE->getLabel(),

        ];
    }
}
