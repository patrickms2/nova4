<?php

namespace App\Filament\App\Community\Resources\WorkOrdersTasks\Pages;

use App\Filament\App\Community\Resources\WorkOrdersTasks\WorkOrdersTasksResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkOrder extends ViewRecord
{
    protected static string $resource = WorkOrdersTasksResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
