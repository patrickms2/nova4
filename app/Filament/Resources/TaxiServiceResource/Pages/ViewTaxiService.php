<?php

namespace App\Filament\Resources\TaxiServiceResource\Pages;
use App\Filament\Resources\TaxiServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
class ViewTaxiService extends ViewRecord
{
    protected static string $resource = TaxiServiceResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
