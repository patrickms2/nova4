<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Pages;

use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use App\Support\PortalTaxistaContext;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxistaExpense extends CreateRecord
{
    protected static string $resource = TaxistaExpenseResource::class;

    protected static ?string $title = 'Nuevo Gasto';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();

        if (PortalTaxistaContext::isPortalPanel()) {
            $data['taxista_user_id'] = PortalTaxistaContext::taxistaUserId();
        }

        return $data;
    }
}
