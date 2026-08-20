<?php

namespace App\Filament\TourAdmin\Resources\TourBookingResource\Pages;

use App\Filament\Resources\TourBookingResource;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;

class ListTourBookings extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = TourBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
