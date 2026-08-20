<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Pages;

use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Support\PortalTaxistaContext;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxistaAppointment extends EditRecord
{
    protected static string $resource = TaxistaAppointmentResource::class;

    protected static ?string $title = 'Editar cita taxi';

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
