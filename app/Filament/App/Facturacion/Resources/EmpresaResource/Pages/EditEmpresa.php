<?php

namespace App\Filament\App\Facturacion\Resources\EmpresaResource\Pages;

use App\Filament\App\Facturacion\Resources\EmpresaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmpresa extends EditRecord
{
    protected static string $resource = EmpresaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
