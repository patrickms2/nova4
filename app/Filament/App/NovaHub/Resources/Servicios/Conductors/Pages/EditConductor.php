<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Conductors\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\Conductors\ConductorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConductor extends EditRecord
{
    protected static string $resource = ConductorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
