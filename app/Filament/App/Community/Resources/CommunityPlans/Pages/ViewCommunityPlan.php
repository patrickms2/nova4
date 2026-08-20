<?php

namespace App\Filament\App\Community\Resources\CommunityPlans\Pages;

use App\Filament\App\Community\Actions\GeneratePlanWorkOrdersAction;
use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityPlan extends ViewRecord
{
    protected static string $resource = CommunityPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), GeneratePlanWorkOrdersAction::make()];
    }
}
