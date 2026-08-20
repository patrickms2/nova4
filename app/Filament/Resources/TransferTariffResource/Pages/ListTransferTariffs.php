<?php

namespace App\Filament\Resources\TransferTariffResource\Pages;

use App\Filament\Resources\TransferTariffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransferTariffs extends ListRecords
{
    protected static string $resource = TransferTariffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
