<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Filament\App\Resources\TaxistaExpenses\Schemas\TaxistaExpenseForm;
use App\Filament\App\Resources\TaxistaExpenses\Tables\TaxistaExpensesTable;
use App\Filament\App\Resources\Taxistas\TaxistaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ManageGastosTaxista extends ManageRelatedRecords
{
    protected static string $resource = TaxistaResource::class;

    protected static string $relationship = 'expenses';

    protected static ?string $navigationLabel = 'Gastos';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        $record = \Livewire\Livewire::current()->getRecord();

        return (string) $record->expenses()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return (string) ($this->getRecord()->name ?? 'Taxista');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Gastos y pagos asociados al taxista.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo gasto')
                ->fillForm(fn (): array => [
                    'taxista_user_id' => (int) $this->getRecord()->id,
                    'created_by_user_id' => auth()->id(),
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['taxista_user_id'] = (int) $this->getRecord()->id;
                    $data['created_by_user_id'] = auth()->id();

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return TaxistaExpenseForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return TaxistaExpensesTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->where('taxista_user_id', (int) $this->getRecord()->id));
    }
}
