<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Pages;

use App\Filament\App\Resources\TaxistaExpenses\Schemas\TaxistaExpensePaymentForm;
use App\Filament\App\Resources\TaxistaExpenses\Tables\TaxistaExpensePaymentsTable;
use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ManageTaxistaExpensePayments extends ManageRelatedRecords
{
    protected static string $resource = TaxistaExpenseResource::class;

    protected static string $relationship = 'payments';

    protected static ?string $title = 'Gestionar pagos';

    public static function getNavigationLabel(): string
    {
        return 'Pagos';
    }

    public function form(Schema $schema): Schema
    {
        $paymentSchema = TaxistaExpensePaymentForm::configure($schema);

        $existingComponents = $paymentSchema->getComponents(withActions: true, withHidden: true);

        $paymentSchema->components(array_merge([
            Hidden::make('taxista_expense_id')
                ->default(fn ($livewire) => $livewire->getOwnerRecord()?->id),
        ], $existingComponents));

        return $paymentSchema;
    }

    public function table(Table $table): Table
    {
        return TaxistaExpensePaymentsTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo pago')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['taxista_expense_id'] = $this->getOwnerRecord()->id;
                        $data['paid_by_user_id'] = auth()->id();

                        return $data;
                    }),
            ]);
    }
}
