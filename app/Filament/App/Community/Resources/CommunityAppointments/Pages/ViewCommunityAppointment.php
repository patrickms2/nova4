<?php

namespace App\Filament\App\Community\Resources\CommunityAppointments\Pages;

use App\Filament\App\Community\Resources\CommunityAppointments\CommunityAppointmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityAppointment extends ViewRecord
{
    protected static string $resource = CommunityAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
