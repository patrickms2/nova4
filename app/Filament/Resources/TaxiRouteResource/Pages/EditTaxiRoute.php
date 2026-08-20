<?php

namespace App\Filament\Resources\TaxiRouteResource\Pages;

use App\Filament\Resources\TaxiRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxiRoute extends EditRecord
{
    protected static string $resource = TaxiRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
