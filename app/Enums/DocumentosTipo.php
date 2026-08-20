<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentosTipo: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case varios = 'varios';
    case cuota = 'cuota';
    case seguro = 'seguro';
    case repuesto = 'repuesto';
    case nomina = 'nomina';
    case impuesto = 'impuesto';
    case servicio = 'servicio';

    public static function getColorBySlug(?string $slug): ?string
    {
        return self::tryFrom($slug)?->getColor();
    }

    public static function getLabelBySlug(?string $slug): ?string
    {
        return self::tryFrom($slug)?->getLabel();
    }

    public static function getIconBySlug(?string $slug): ?string
    {
        return self::tryFrom($slug)?->getIcon();
    }

    public static function getValueBySlug(?string $slug): ?string
    {

        return self::tryFrom($slug)?->getSlugs();
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public static function getColors(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getColor()])
            ->toArray();
    }

    public static function getIcons(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getIcon()])
            ->toArray();
    }

    public static function options(): array
    {
        return [
            self::varios->value => self::varios->getLabel(),
            self::cuota->value => self::cuota->getLabel(),
            self::seguro->value => self::seguro->getLabel(),
            self::repuesto->value => self::repuesto->getLabel(),
            self::nomina->value => self::nomina->getLabel(),
            self::impuesto->value => self::impuesto->getLabel(),
            self::servicio->value => self::servicio->getLabel(),
        ];
    }

    public function getSlugs(): ?string
    {
        return match ($this) {
            self::varios => 'varios',
            self::cuota => 'cuota',
            self::seguro => 'seguro',
            self::repuesto => 'repuesto',
            self::nomina => 'nomina',
            self::impuesto => 'impuesto',
            self::servicio => 'servicio',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::varios => 'Varios',
            self::cuota => 'Cuotas',
            self::seguro => 'Seguros',
            self::repuesto => 'repuestos',
            self::nomina => 'Nominas',
            self::impuesto => 'Impuestos',
            self::servicio => 'Servicios',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::varios => 'Varios',
            self::cuota => 'Cuotas',
            self::seguro => 'Seguros',
            self::repuesto => 'repuestos',
            self::nomina => 'Nominas',
            self::impuesto => 'Impuestos',
            self::servicio => 'Servicios',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::varios => 'purple',
            self::cuota => 'orange',
            self::seguro => 'indigo',
            self::repuesto => 'orange',
            self::nomina => 'green',
            self::impuesto => 'blue',
            self::servicio => 'yellow',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::varios => 'heroicon-s-clock',
            self::cuota => 'heroicon-s-x-mark',
            self::seguro => 'heroicon-s-x-mark',
            self::repuesto => 'heroicon-s-x-mark',
            self::nomina => 'heroicon-s-x-mark',
            self::impuesto => 'heroicon-s-exclamation-circle',
            self::servicio => 'heroicon-s-clock',
        };
    }
}
