<?php

namespace App\Filament\Resources\NovaModuleResource\Pages;

use App\Filament\Resources\NovaModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNovaModule extends EditRecord
{
    protected static string $resource = NovaModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

