<?php

namespace App\Filament\App\Rentals\Resources\RentalTaskResource\Pages;

use App\Filament\App\Rentals\Resources\RentalTaskResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRentalTasks extends ListRecords
{
    protected static string $resource = RentalTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('warning')
                ->url(RentalTaskResource::getUrl('kanban')),

            Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->url(RentalTaskResource::getUrl('calendar')),

    CreateAction::make()->slideOver(false)->icon('heroicon-o-plus')
                ->color('danger')
                ->button()
                ->hiddenLabel(),

        ];
    }
}
