<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\Pages;

use App\Filament\App\Rentals\Resources\RentalReservationResource;
use App\Filament\App\Rentals\Resources\RentalReservationResource\Widgets\RentalReservationsCalendar;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Widgets\Widget;

class CalendarRentalReservations extends Page
{
    protected static string $resource = RentalReservationResource::class;

    protected static ?string $title = 'Calendario de reservas';

    protected string $view = 'filament.pages.rental-occupancy-calendar';

    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('table')
                ->label('Vista tabla')
                ->icon('heroicon-o-table-cells')
                ->url(RentalReservationResource::getUrl('index')),
                
            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->url(RentalReservationResource::getUrl('kanban')),
        ];
    }

    /** @return array<class-string<Widget>> */
    protected function getHeaderWidgets(): array
    {
        return [RentalReservationsCalendar::class];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
