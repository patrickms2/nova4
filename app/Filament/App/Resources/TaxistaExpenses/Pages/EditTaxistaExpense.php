<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Pages;

use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use App\Support\PortalTaxistaContext;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxistaExpense extends EditRecord
{
    protected static string $resource = TaxistaExpenseResource::class;

    protected static ?string $title = 'Editar gasto';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
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
