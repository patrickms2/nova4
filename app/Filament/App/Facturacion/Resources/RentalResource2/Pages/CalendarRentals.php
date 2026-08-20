<?php

declare(strict_types=1);

namespace App\Filament\App\Facturacion\Resources\RentalResource2\Pages;

use App\Filament\App\Facturacion\Resources\RentalResource2;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\Page;
use App\Filament\Widgets\RentalCalendarWidget;

class CalendarRentals extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string $resource = RentalResource2::class;

    protected static ?string $navigationLabel = 'Calendario Alquileres';

    protected static ?string $title = 'Calendario Alquileres';

    protected static string|\UnitEnum|null $navigationGroup = 'Reservas';
    protected static ?string $navigationParentGroup = 'Res. Alquileres';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.rental-calendar';

    public function getWidgets(): array
    {
        return [
            RentalCalendarWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
