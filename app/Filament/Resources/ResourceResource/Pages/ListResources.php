<?php

namespace App\Filament\Resources\ResourceResource\Pages;
use App\Filament\Resources\ResourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
class ListResources extends ListRecords
{
    use AdvancedTables;
    
    protected static string $resource = ResourceResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
