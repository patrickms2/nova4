<?php

namespace App\Filament\App\Community\Resources\WorkOrders\Pages;

use App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkOrder extends ViewRecord
{
    protected static string $resource = WorkOrderResource::class;
    protected static ?string $navigationLabel = 'Ver Órden';

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
