<?php

namespace App\Filament\Resources\ExternalOrderResource\Pages;
use App\Filament\Resources\ExternalOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
class ListExternalOrders extends ListRecords
{
    protected static string $resource = ExternalOrderResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
