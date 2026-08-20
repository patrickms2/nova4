<?php

namespace App\Filament\App\Rentals\Resources\RentalExpenseResource\Pages;

use App\Filament\App\Rentals\Resources\RentalExpenseResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListRentalExpenses extends ListRecords
{
    protected static string $resource = RentalExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('warning')
                ->url(RentalExpenseResource::getUrl('kanban')),
        ];
    }
}
