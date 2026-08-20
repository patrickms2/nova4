<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Municipios\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\Municipios\MunicipiosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMunicipios extends EditRecord
{
    protected static string $resource = MunicipiosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
