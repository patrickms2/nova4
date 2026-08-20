<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Schemas;

use App\Enums\TaxistaExpensePaymentType;
use App\Enums\TaxistaExpenseStatus;
use App\Models\TaxistaExpenseSubcategory;
use App\Support\PortalTaxistaContext;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TaxistaExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('created_by_user_id')
                    ->default(fn (): ?int => auth()->id()),

                Section::make('Gasto del taxista')
                    ->schema([
                        Select::make('taxista_user_id')
                            ->label('Taxista')
                            ->relationship(
                                'taxista',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaOptions($query)
                            )
                            ->default(fn (): ?int => PortalTaxistaContext::taxistaUserId())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->hidden(fn (): bool => PortalTaxistaContext::isPortalPanel()),

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
                                ->toArray()
                            )
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
                            ->prefix('EUR')
                            ->helperText('Si queda vacio se usara el importe base.'),

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
                            ->required()
                            ->native(false)
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
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
