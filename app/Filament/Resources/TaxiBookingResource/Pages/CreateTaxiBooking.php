<?php

namespace App\Filament\Resources\TaxiBookingResource\Pages;
use App\Filament\Resources\TaxiBookingResource;
use Filament\Resources\Pages\CreateRecord;
class CreateTaxiBooking extends CreateRecord
{
    protected static string $resource = TaxiBookingResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
