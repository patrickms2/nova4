<?php

namespace App\Filament\Resources\TaxiBookingResource\Pages;
use App\Filament\Resources\TaxiBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
class ViewTaxiBooking extends ViewRecord
{
    protected static string $resource = TaxiBookingResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
