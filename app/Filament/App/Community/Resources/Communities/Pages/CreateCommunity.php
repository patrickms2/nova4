<?php

namespace App\Filament\App\Community\Resources\Communities\Pages;

use App\Filament\App\Community\Resources\Communities\CommunityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunity extends CreateRecord
{
    protected static string $resource = CommunityResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
