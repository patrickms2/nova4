<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Pages;

use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Support\PortalTaxistaContext;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxistaDocument extends CreateRecord
{
    protected static string $resource = TaxistaDocumentResource::class;

    protected static ?string $title = 'Nuevo documento';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by_user_id'] = $data['uploaded_by_user_id'] ?? auth()->id();

        if (PortalTaxistaContext::isPortalPanel()) {
            $data['taxista_user_id'] = PortalTaxistaContext::taxistaUserId();
        }

        return $data;
    }
}
