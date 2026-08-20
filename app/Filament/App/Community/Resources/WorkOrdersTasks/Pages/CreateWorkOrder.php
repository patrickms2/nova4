<?php

namespace App\Filament\App\Community\Resources\WorkOrdersTasks\Pages;

use App\Filament\App\Community\Resources\WorkOrdersTasks\WorkOrdersTasksResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkOrder extends CreateRecord
{
    protected static string $resource = WorkOrdersTasksResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
