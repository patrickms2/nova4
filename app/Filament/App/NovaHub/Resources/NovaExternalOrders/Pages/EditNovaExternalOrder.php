<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalOrders\Pages;

use App\Filament\App\NovaHub\Resources\NovaExternalOrders\NovaExternalOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditNovaExternalOrder extends EditRecord
{
    protected static string $resource = NovaExternalOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
