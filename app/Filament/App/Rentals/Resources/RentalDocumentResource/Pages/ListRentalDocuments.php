<?php

namespace App\Filament\App\Rentals\Resources\RentalDocumentResource\Pages;

use App\Filament\App\Rentals\Resources\RentalDocumentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListRentalDocuments extends ListRecords
{
    protected static string $resource = RentalDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('warning')
                ->url(RentalDocumentResource::getUrl('kanban')),
        ];
    }
}
