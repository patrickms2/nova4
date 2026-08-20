<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum TaskStatus: string implements HasColor, HasIcon, HasLabel
{
    case Backlog = 'backlog';

    case Todo = 'todo';

    case InProgress = 'in_progress';

    case InReview = 'in_review';

    case Completed = 'completed';

    case Cancelled = 'cancelled';

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
            self::Todo->value => self::Todo->getLabel(),
            self::InProgress->value => self::InProgress->getLabel(),

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
            self::Todo->value => 'Todo',
            self::InProgress->value => 'InProgress',
            self::InReview => 'InReview',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
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
            self::Todo => 'Todo',
            self::InProgress => 'En Progreso',
            self::InReview => 'InReview',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
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
            self::Backlog => 'gray',
            self::Todo => 'info',
            self::InProgress => 'warning',
            self::InReview => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Backlog => 'heroicon-s-inboxstack',
            self::Todo => 'heroicon-s-queue-list',
            self::InProgress => 'heroicon-s-arrow-path',
            self::InReview => 'heroicon-s-eye',
            self::Completed => 'heroicon-s-check-circle',
            self::Cancelled => 'heroicon-s-x-circle',
        };
    }
}
