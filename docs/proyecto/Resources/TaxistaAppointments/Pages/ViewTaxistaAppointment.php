<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Pages;

use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTaxistaAppointment extends ViewRecord
{
    protected static string $resource = TaxistaAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
