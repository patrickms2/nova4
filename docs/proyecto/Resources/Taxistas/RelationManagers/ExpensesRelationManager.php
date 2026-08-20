<?php

namespace App\Filament\App\Resources\Taxistas\RelationManagers;

use App\Enums\TaxistaExpensePaymentType;
use App\Enums\TaxistaExpenseStatus;
use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use App\Models\TaxistaExpenseSubcategory;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Cobros';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('taxista_user_id')
                    ->default(fn (): int => (int) $this->getOwnerRecord()->id)
                    ->required(),

                Hidden::make('created_by_user_id')
                    ->default(fn (): ?int => auth()->id()),

                Section::make('Cobro')
                    ->schema([
                        Select::make('booking_department_id')
                            ->label('Departamento')
                            ->relationship(
                                'department',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->orderBy('name')
                            )
                            ->searchable()
                            ->preload(),

                        TextInput::make('title')
                            ->label('Concepto')
                            ->required()
                            ->maxLength(255),

                        Select::make('taxista_expense_category_id')
                            ->label('Categoria')
                            ->relationship(
                                'category',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->orderBy('sort')
                                    ->orderBy('name')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('taxista_expense_subcategory_id', null);
                            }),

                        Select::make('taxista_expense_subcategory_id')
                            ->label('Subcategoria')
                            ->options(fn (Get $get): array => TaxistaExpenseSubcategory::query()
                                ->where('taxista_expense_category_id', (int) $get('taxista_expense_category_id'))
                                ->where('is_active', true)
                                ->orderBy('sort')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload(),

                        TextInput::make('amount')
                            ->label('Importe')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('EUR'),

                        TextInput::make('final_amount')
                            ->label('Importe final')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('EUR'),

                        DatePicker::make('expense_date')
                            ->label('Fecha gasto')
                            ->default(now())
                            ->required(),

                        DatePicker::make('due_date')
                            ->label('Vencimiento'),

                        Select::make('payment_type')
                            ->label('Tipo de pago')
                            ->options(TaxistaExpensePaymentType::class)
                            ->default(TaxistaExpensePaymentType::Onetime)
                            ->native(false)
                            ->required()
                            ->live(),

                        TextInput::make('recurring_count')
                            ->label('Cuotas')
                            ->numeric()
                            ->integer()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->visible(fn (Get $get): bool => $get('payment_type') === TaxistaExpensePaymentType::Recurring->value),

                        Select::make('status')
                            ->label('Estado')
                            ->options(TaxistaExpenseStatus::class)
                            ->default(TaxistaExpenseStatus::Pending)
                            ->required()
                            ->native(false),

                        Toggle::make('is_priority')
                            ->label('Prioritario')
                            ->inline(false),

                        FileUpload::make('attachment_path')
                            ->label('Adjunto')
                            ->disk('public')
                            ->directory('taxistas/expenses')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/*',
                                '.doc',
                                '.docx',
                                '.xls',
                                '.xlsx',
                            ]),

                        Textarea::make('description')
                            ->label('Descripcion')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Concepto')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Importe')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label('Pagado')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                TextColumn::make('payment_type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('expense_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo cobro')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['taxista_user_id'] = (int) $this->getOwnerRecord()->id;
                        $data['created_by_user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('payments')
                    ->label('Pagos')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn ($record): string => TaxistaExpenseResource::getUrl('payments', ['record' => $record]))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
