<?php

namespace App\Filament\Resources\ExternalBookingResource\Pages;
use App\Filament\Resources\ExternalBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditExternalBooking extends EditRecord
{
    protected static string $resource = ExternalBookingResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
