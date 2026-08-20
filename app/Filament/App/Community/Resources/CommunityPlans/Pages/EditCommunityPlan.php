<?php

namespace App\Filament\App\Community\Resources\CommunityPlans\Pages;

use App\Filament\App\Community\Actions\GeneratePlanWorkOrdersAction;
use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityPlan extends EditRecord
{
    protected static string $resource = CommunityPlanResource::class;

    protected static ?string $navigationLabel = 'Editar Plan';

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make(), GeneratePlanWorkOrdersAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
