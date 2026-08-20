<?php

namespace App\Filament\App\Facturacion\Resources\EmpresaResource\Pages;

use App\Filament\App\Facturacion\Resources\EmpresaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmpresas extends ListRecords
{
    protected static string $resource = EmpresaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
