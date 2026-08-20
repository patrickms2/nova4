<?php

namespace App\Filament\Resources\PanelTableResource\Pages;

use App\Filament\Resources\PanelTableResource;
use Filament\Resources\Pages\ListRecords;

class ListPanelTables extends ListRecords
{
    protected static string $resource = PanelTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
