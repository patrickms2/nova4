<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\TipoTaxis\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\TipoTaxis\TipoTaxisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTipoTaxis extends CreateRecord
{
    protected static string $resource = TipoTaxisResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
