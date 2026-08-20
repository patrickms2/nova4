<?php

namespace App\Filament\Resources\TaxiBookingResource\Pages;
use App\Filament\Resources\TaxiBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditTaxiBooking extends EditRecord
{
    protected static string $resource = TaxiBookingResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
