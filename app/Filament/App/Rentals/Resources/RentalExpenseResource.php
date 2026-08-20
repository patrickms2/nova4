<?php

namespace App\Filament\App\Rentals\Resources;

use App\Filament\App\Rentals\Resources\RentalExpenseResource\Pages;
use App\Filament\App\Rentals\Resources\RentalExpenseResource\RelationManagers;
use App\Filament\App\Rentals\Resources\RentalExpenseResource\Schemas\RentalReservationInfolist;
use App\Filament\App\Rentals\Rentals;

use App\Models\RentalExpense;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Enums\SubNavigationPosition;

class RentalExpenseResource extends Resource
{
    protected static ?string $model = RentalExpense::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Gastos';

    protected static ?string $pluralModelLabel = 'Gastos';
    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';
    protected static ?string $cluster = Rentals::class;
    protected static ?string $modelLabel = 'Gasto';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del gasto')
                    ->schema([
                        Select::make('rental_property_id')
                            ->label('Propiedad')
                            ->relationship('rentalProperty', 'name')
                            ->required(),
                        Select::make('category')
                            ->label('Categoría')
                            ->options(RentalExpense::categories())
                            ->required(),
                        DatePicker::make('expense_date')
                            ->label('Fecha del gasto')
                            ->required()
                            ->default(now()),
                        TextInput::make('provider_name')
                            ->label('Proveedor')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(3),
                    ])
                    ->columns(2),
                Section::make('Importes')
                    ->schema([
                        TextInput::make('base_amount')
                            ->label('Base imponible')
                            ->numeric()
                            ->prefix('€')
                            ->default(0),
                        TextInput::make('tax_amount')
                            ->label('Impuesto')
                            ->numeric()
                            ->prefix('€')
                            ->default(0),
                        TextInput::make('total_amount')
                            ->label('Total')
                            ->numeric()
                            ->prefix('€')
                            ->default(0),
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'paid' => 'Pagado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->default('pending')
                            ->required(),
                        Toggle::make('is_recurrent')
                            ->label('Recurrente'),
                        FileUpload::make('document_path')
                            ->label('Documento')
                            ->directory('rental-expenses')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('expense_date', 'desc')
            ->columns([
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RentalExpense::categories()[$state] ?? $state),
                TextColumn::make('rentalProperty.name')
                    ->label('Propiedad'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('expense_date')
                    ->label('Fecha')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(RentalExpense::categories()),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                        'cancelled' => 'Cancelado',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Crear gasto')
                    ->icon(Heroicon::OutlinedPlus)
                    ->slideOver(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['rentalProperty']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentalExpenses::route('/'),
            'kanban' => Pages\KanbanRentalExpenses::route('/kanban'),
            'view' => Pages\ViewRentalExpense::route('/{record}'),
        ];
    }
}
