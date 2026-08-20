<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalOrders\Pages;

use App\Filament\App\NovaHub\Resources\NovaExternalOrders\NovaExternalOrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNovaExternalOrder extends ViewRecord
{
    protected static string $resource = NovaExternalOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
