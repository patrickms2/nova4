<?php

namespace App\Filament\App\Community\Resources\CommunityPlans\Pages;

use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityPlans extends ListRecords
{
    protected static string $resource = CommunityPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
