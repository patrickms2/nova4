<?php

namespace App\Filament\App\Rentals\Resources\RentalPropertyResource\Pages;

use App\Filament\App\Rentals\Resources\RentalPropertyResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
class ListRentalProperties extends ListRecords
{
    protected static string $resource = RentalPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [

    CreateAction::make()->slideOver(false)->icon('heroicon-o-plus')
                ->color('danger')
                ->button()
                ->hiddenLabel(),

        ];
    }

}
