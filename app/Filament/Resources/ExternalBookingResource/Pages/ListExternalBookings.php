<?php

namespace App\Filament\Resources\ExternalBookingResource\Pages;
use App\Filament\Resources\ExternalBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
class ListExternalBookings extends ListRecords
{
    use AdvancedTables;
    
    protected static string $resource = ExternalBookingResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
