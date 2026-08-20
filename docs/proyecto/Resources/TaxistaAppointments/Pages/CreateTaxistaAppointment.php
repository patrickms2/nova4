<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Pages;

use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Support\PortalTaxistaContext;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxistaAppointment extends CreateRecord
{
    protected static string $resource = TaxistaAppointmentResource::class;

    protected static ?string $title = 'Nueva cita taxi';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = $data['created_by_user_id'] ?? auth()->id();

        if (PortalTaxistaContext::isPortalPanel()) {
            $data['taxista_user_id'] = PortalTaxistaContext::taxistaUserId();
        }

        return $data;
    }
}
