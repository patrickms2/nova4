<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\Pages;

use App\Filament\App\Rentals\Resources\RentalReservationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListRentalReservations extends ListRecords
{
    protected static string $resource = RentalReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->url(RentalReservationResource::getUrl('calendar')),
            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('warning')
                ->url(RentalReservationResource::getUrl('kanban')),
        ];
    }
}
