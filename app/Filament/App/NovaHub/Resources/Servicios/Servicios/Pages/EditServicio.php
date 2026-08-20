<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Servicios\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\Servicios\ServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\DeleteAction;

class EditServicio extends EditRecord
{
    protected static string $resource = ServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
