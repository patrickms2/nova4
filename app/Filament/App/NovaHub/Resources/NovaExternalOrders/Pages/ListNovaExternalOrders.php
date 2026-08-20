<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalOrders\Pages;

use App\Filament\App\NovaHub\Resources\NovaExternalOrders\NovaExternalOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNovaExternalOrders extends ListRecords
{
    protected static string $resource = NovaExternalOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
