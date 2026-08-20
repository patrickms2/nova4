<?php

namespace App\Filament\Resources\TaxiBookingResource\Pages;
use App\Filament\Resources\TaxiBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
class ListTaxiBookings extends ListRecords
{
    use AdvancedTables;
    
    protected static string $resource = TaxiBookingResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
