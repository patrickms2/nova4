<?php

namespace App\Filament\App\Community\Resources\CommunityAppointments\Pages;

use App\Filament\App\Community\Resources\CommunityAppointments\CommunityAppointmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityAppointment extends CreateRecord
{
    protected static string $resource = CommunityAppointmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'created_by' => auth()->id()];
    }
}
