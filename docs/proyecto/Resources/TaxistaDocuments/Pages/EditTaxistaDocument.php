<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Pages;

use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Support\PortalTaxistaContext;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxistaDocument extends EditRecord
{
    protected static string $resource = TaxistaDocumentResource::class;

    protected static ?string $title = 'Editar documento';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            $data['taxista_user_id'] = PortalTaxistaContext::taxistaUserId();
        }

        return $data;
    }
}
