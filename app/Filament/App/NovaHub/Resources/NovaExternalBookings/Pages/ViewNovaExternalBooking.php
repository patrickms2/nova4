<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalBookings\Pages;

use App\Filament\App\NovaHub\Resources\NovaExternalBookings\NovaExternalBookingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNovaExternalBooking extends ViewRecord
{
    protected static string $resource = NovaExternalBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
