<?php

namespace App\Filament\Resources\NovaModuleResource\Pages;

use App\Filament\Resources\NovaModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNovaModules extends ListRecords
{
    protected static string $resource = NovaModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
