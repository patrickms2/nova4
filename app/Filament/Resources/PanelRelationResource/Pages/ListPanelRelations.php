<?php

namespace App\Filament\Resources\PanelRelationResource\Pages;

use App\Filament\Resources\PanelRelationResource;
use Filament\Resources\Pages\ListRecords;

class ListPanelRelations extends ListRecords
{
    protected static string $resource = PanelRelationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
