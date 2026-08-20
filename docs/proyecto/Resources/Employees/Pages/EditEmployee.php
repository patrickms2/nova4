<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Employees\EmployeeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected static ?string $title = 'Editar empleado';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['is_employee'] = true;
        $data['role'] = 'empleado';

        return $data;
    }
}
