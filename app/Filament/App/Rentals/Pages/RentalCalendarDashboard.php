<?php

namespace App\Filament\App\Rentals\Pages;

use App\Filament\App\Rentals\Widgets\CasaElPatioCalendarWidget;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use App\Filament\App\Rentals\Rentals;

class RentalCalendarDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendarios';

    protected static ?string $title = 'Calendario de Casa El Patio';

    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';
    protected static ?string $cluster = Rentals::class;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.rental-calendar';

    /**
     * @return array<class-string<Widget>>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            CasaElPatioCalendarWidget::class,
        ];
    }
}
