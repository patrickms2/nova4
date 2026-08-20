<?php

namespace App\Filament\App\Community\Resources\Owners\Pages;

use App\Filament\App\Community\Resources\Owners\OwnerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOwner extends CreateRecord
{
    protected static string $resource = OwnerResource::class;

    protected function afterCreate(): void
    {
        $this->record->roles()->firstOrCreate(['role' => 'owner']);
    }
}
