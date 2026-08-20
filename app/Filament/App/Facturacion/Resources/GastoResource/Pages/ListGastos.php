<?php

namespace App\Filament\App\Facturacion\Resources\GastoResource\Pages;

use App\Filament\App\Facturacion\Resources\GastoResource;
use App\Filament\Widgets\GastosStatsWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Width;

class ListGastos extends ListRecords
{
    protected static string $resource = GastoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::ScreenTwoExtraLarge),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GastosStatsWidget::class,
        ];
    }
}
