<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Pages;

use App\Enums\TaxistaExpensePaymentType;
use App\Enums\TaxistaExpenseStatus;
use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use App\Models\TaxistaExpense;
use App\Support\PortalTaxistaContext;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTaxistaExpenses extends ListRecords
{
    protected static string $resource = TaxistaExpenseResource::class;

    protected static ?string $title = 'Gastos';

    public function getTitle(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Mis Gastos';
        }

        return 'Gastos';
    }

    public function getSubheading(): ?string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Control de gastos y pagos del taxista.';
        }

        return null;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()->label('Todo'),
            'priority' => Tab::make()
                ->label('Prioritario')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaExpense::query())
                    ->where('is_priority', true)
                    ->count())
                ->badgeColor('warning')
                ->icon('heroicon-m-shield-exclamation')
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where('is_priority', true)),
            'onetime' => Tab::make()
                ->label('Unico')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaExpense::query())
                    ->where('payment_type', TaxistaExpensePaymentType::Onetime->value)
                    ->count())
                ->badgeColor('gray')
                ->icon(TaxistaExpensePaymentType::Onetime->getIcon())
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where('payment_type', TaxistaExpensePaymentType::Onetime->value)),
            'recurring' => Tab::make()
                ->label('Recurrente')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaExpense::query())
                    ->where('payment_type', TaxistaExpensePaymentType::Recurring->value)
                    ->count())
                ->badgeColor('gray')
                ->icon(TaxistaExpensePaymentType::Recurring->getIcon())
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where('payment_type', TaxistaExpensePaymentType::Recurring->value)),
            'completed' => Tab::make()
                ->label('Completado')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaExpense::query())
                    ->where('status', TaxistaExpenseStatus::Completed->value)
                    ->count())
                ->badgeColor(TaxistaExpenseStatus::Completed->getColor())
                ->icon(TaxistaExpenseStatus::Completed->getIcon())
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where('status', TaxistaExpenseStatus::Completed->value)),
            'partial' => Tab::make()
                ->label('Parcial')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaExpense::query())
                    ->where('status', TaxistaExpenseStatus::Partial->value)
                    ->count())
                ->badgeColor(TaxistaExpenseStatus::Partial->getColor())
                ->icon(TaxistaExpenseStatus::Partial->getIcon())
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where('status', TaxistaExpenseStatus::Partial->value)),
            'pending' => Tab::make()
                ->label('Pendiente')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaExpense::query())
                    ->where('status', TaxistaExpenseStatus::Pending->value)
                    ->count())
                ->badgeColor(TaxistaExpenseStatus::Pending->getColor())
                ->icon(TaxistaExpenseStatus::Pending->getIcon())
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where('status', TaxistaExpenseStatus::Pending->value)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo gasto'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return TaxistaExpenseResource::getWidgets();
    }
}
