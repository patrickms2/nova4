<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\TipoTaxis\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\TipoTaxis\TipoTaxisResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTipoTaxis extends EditRecord
{
    protected static string $resource = TipoTaxisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
