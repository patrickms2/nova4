<?php

namespace App\Filament\Resources\PanelFieldResource\Pages;

use App\Filament\Resources\PanelFieldResource;
use Filament\Resources\Pages\ListRecords;

class ListPanelFields extends ListRecords
{
    protected static string $resource = PanelFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
