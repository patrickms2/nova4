<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\UserRole;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxista extends CreateRecord
{
    protected static string $resource = TaxistaResource::class;

    protected static ?string $title = 'Crear taxista';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::SERVICE->value;
        $data['status'] = (bool) ($data['status'] ?? true);

        return $data;
    }
}
