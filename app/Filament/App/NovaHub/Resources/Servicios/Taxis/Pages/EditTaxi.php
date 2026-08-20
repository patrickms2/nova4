<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Taxis\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\Taxis\TaxiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxi extends EditRecord
{
    protected static string $resource = TaxiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
