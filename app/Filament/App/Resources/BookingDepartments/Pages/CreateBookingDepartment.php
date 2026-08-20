<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookingDepartment extends CreateRecord
{
    protected static string $resource = BookingDepartmentResource::class;

    protected static ?string $title = 'Crear departamento';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
