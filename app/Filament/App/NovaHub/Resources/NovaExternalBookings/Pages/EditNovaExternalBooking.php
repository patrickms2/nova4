<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalBookings\Pages;

use App\Filament\App\NovaHub\Resources\NovaExternalBookings\NovaExternalBookingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditNovaExternalBooking extends EditRecord
{
    protected static string $resource = NovaExternalBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
