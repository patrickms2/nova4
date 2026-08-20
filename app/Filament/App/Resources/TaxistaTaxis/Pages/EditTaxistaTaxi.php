<?php

namespace App\Filament\App\Resources\TaxistaTaxis\Pages;

use App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource;
use App\Support\PortalTaxistaContext;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxistaTaxi extends EditRecord
{
    protected static string $resource = TaxistaTaxiResource::class;

    protected static ?string $title = 'Editar taxi';

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
