<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;

enum CitaStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case pendiente = 'pendiente';
    case confirmada = 'confirmada';
    case finalizada = 'finalizada';
    case cancelada = 'cancelada';
    case anulada = 'anulada';
  public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Solicitado',
            self::Processing => 'En camino',
            self::Shipped => 'En trayecto',
            self::Delivered => 'Finaizado',
            self::Cancelled => 'Cancelado',
        };
    }


    public function getIcon(): string
    {
        return match ($this) {
            self::New => 'heroicon-m-sparkles',
            self::Processing => 'heroicon-m-arrow-path',
            self::Shipped => 'heroicon-m-truck',
            self::Delivered => 'heroicon-m-check-badge',
            self::Cancelled => 'heroicon-m-x-circle',
        };
    }
    public static function getColorBySlug($slug): string|array|null
    {
        return self::tryFrom($slug)->getColor();
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

    public static function getDescriptionBySlug(?string $slug): ?string
    {

        return self::tryFrom($slug)?->getDescription();
    }

    public function getSlugs(): string
    {
        return match ($this) {
            self::pendiente => 'pendiente',
            self::confirmada => 'confirmada',
            self::anulada => 'anulada',
            self::finalizada => 'finalizada',
            self::cancelada => 'cancelada',
        };
    }

    public function getColor(): array|string|null
    {
        return match ($this) {
            self::pendiente => 'warning',
            self::confirmada => 'success',
            self::cancelada => 'danger',
            self::anulada => 'dark',
            self::finalizada => 'info',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::pendiente => Heroicon::OutlinedClock,
            self::confirmada => Heroicon::OutlinedCheckCircle,
            self::cancelada => Heroicon::OutlinedArrowRightOnRectangle,
            self::anulada => Heroicon::OutlinedArchiveBox,
            self::finalizada => Heroicon::OutlinedArrowRightOnRectangle,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::pendiente => 'Pendiente',
            self::confirmada => 'Confirmada',
            self::cancelada => 'Cancelada',
            self::anulada => 'Anulada',
            self::finalizada => 'Finalizada',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::pendiente => 'Pendiente',
            self::confirmada => 'Confirmada',
            self::cancelada => 'Cancelada',
            self::anulada => 'Anulada',
            self::finalizada => 'Finalizada',
        };
    }

    // Static methods for backward compatibility
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

    // Static methods for backward compatibility
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
