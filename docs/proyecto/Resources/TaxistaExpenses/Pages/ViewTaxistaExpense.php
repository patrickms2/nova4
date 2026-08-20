<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Pages;

use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTaxistaExpense extends ViewRecord
{
    protected static string $resource = TaxistaExpenseResource::class;

    protected static ?string $title = 'Detalle de Gasto';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
