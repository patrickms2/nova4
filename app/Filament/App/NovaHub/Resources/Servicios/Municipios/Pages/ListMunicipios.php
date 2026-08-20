<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Municipios\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\Municipios\MunicipiosResource;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMunicipios extends ListRecords
{



    protected static string $resource = MunicipiosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
