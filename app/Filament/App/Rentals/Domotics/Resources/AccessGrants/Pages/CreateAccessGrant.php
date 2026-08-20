<?php

namespace App\Filament\App\Rentals\Domotics\Resources\AccessGrants\Pages;

use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\AccessGrantResource;
use App\Models\Property;
use App\Services\Domotics\PinGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateAccessGrant extends CreateRecord
{
    protected static string $resource = AccessGrantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['pin'] ?? null) && filled($data['property_id'] ?? null)) {
            $data['pin'] = PinGenerator::generate(Property::query()->findOrFail($data['property_id']));
        }

        return $data;
    }
}
