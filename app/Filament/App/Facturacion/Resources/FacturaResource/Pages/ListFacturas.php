<?php

namespace App\Filament\App\Facturacion\Resources\FacturaResource\Pages;

use App\Filament\App\Facturacion\Resources\FacturaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListFacturas extends ListRecords
{
    protected static string $resource = FacturaResource::class;

    protected function getHeaderWidgets(): array
    {
        return FacturaResource::getWidgets();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::ScreenTwoExtraLarge),
        ];
    }
}
