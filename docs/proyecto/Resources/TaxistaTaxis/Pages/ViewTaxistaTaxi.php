<?php

namespace App\Filament\App\Resources\TaxistaTaxis\Pages;

use App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTaxistaTaxi extends ViewRecord
{
    protected static string $resource = TaxistaTaxiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
