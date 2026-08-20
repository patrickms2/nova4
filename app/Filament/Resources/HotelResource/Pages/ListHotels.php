<?php

namespace App\Filament\Resources\HotelResource\Pages;
use App\Filament\Resources\HotelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
class ListHotels extends ListRecords
{
    use AdvancedTables;
    
    protected static string $resource = HotelResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
