<?php

namespace App\Filament\App\Community\Resources\CommunityPlans\Pages;

use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityPlan extends CreateRecord
{
    protected static string $resource = CommunityPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
