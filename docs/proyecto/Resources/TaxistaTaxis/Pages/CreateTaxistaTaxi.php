<?php

namespace App\Filament\App\Resources\TaxistaTaxis\Pages;

use App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource;
use App\Support\PortalTaxistaContext;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxistaTaxi extends CreateRecord
{
    protected static string $resource = TaxistaTaxiResource::class;

    protected static ?string $title = 'Nuevo taxi';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            $data['taxista_user_id'] = PortalTaxistaContext::taxistaUserId();
        }

        return $data;
    }
}
