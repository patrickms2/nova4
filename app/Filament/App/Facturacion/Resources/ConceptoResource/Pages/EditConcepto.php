<?php

namespace App\Filament\App\Facturacion\Resources\ConceptoResource\Pages;

use App\Filament\App\Facturacion\Resources\ConceptoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConcepto extends EditRecord
{
    protected static string $resource = ConceptoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
