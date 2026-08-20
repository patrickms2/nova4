<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalBookings\Pages;

use App\Filament\App\NovaHub\Resources\NovaExternalBookings\NovaExternalBookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNovaExternalBookings extends ListRecords
{
    protected static string $resource = NovaExternalBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
