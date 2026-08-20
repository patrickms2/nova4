<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\TipoTaxis\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\TipoTaxis\TipoTaxisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTipoTaxis extends ListRecords
{
    protected static string $resource = TipoTaxisResource::class;
    protected static ?string $title = 'Tipo Taxis';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
