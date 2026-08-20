<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\UserRole;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxista extends EditRecord
{
    protected static string $resource = TaxistaResource::class;

    protected static ?string $title = 'Editar taxista';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['role'] = UserRole::SERVICE->value;

        return $data;
    }
}
