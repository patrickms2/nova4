<?php

namespace App\Filament\App\Rentals\Resources;

use App\Filament\App\Rentals\Rentals;
use App\Filament\App\Rentals\Resources\RentalPropertyResource\Pages;
use App\Models\RentalProperty;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RentalPropertyResource extends Resource
{
    protected static ?string $model = RentalProperty::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Propiedades Alquiler';

    protected static ?string $pluralModelLabel = 'Propiedades';

    protected static ?string $modelLabel = 'Propiedad';

        protected static string|\UnitEnum|null $navigationGroup = 'NOVA Property';
    protected static ?string $navigationParentGroup = 'Personas';
    protected static ?string $cluster = Rentals::class;
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información general')
                    ->schema([
                        Select::make('property_id')
                            ->label('Inmueble base')
                            ->relationship('property', 'name')
                            ->nullable(),
                        TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('nickname')
                            ->label('Apodo')
                            ->nullable(),
                        Textarea::make('address')
                            ->label('Dirección')
                            ->rows(2)
                            ->nullable(),
                        TextInput::make('tourist_registry')
                            ->label('Registro turístico')
                            ->nullable(),
                        TextInput::make('cadastral_reference')
                            ->label('Referencia catastral')
                            ->nullable(),
                        Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Reglas financieras')
                    ->description('Configura comisiones y servicios para el cálculo automático de liquidaciones.')
                    ->schema([
                        KeyValue::make('financial_settings')
                            ->label('Reglas')
                            ->keyLabel('Concepto')
                            ->valueLabel('Valor')
                            ->addable()
                            ->editableKeys()
                            ->deletable()
                            ->default([
                                'manager_commission_rate' => '30',
                                'cleaning_per_stay' => '90',
                                'laundry_per_guest' => '15',
                                'welcome_pack' => '25',
                                'damage_waiver' => '20',
                            ]),
                    ]),
                Section::make('Ajustes')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('Configuración extra')
                            ->keyLabel('Clave')
                            ->valueLabel('Valor')
                            ->addable()
                            ->editableKeys()
                            ->deletable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.name')
                    ->label('Inmueble base'),
                TextColumn::make('reservations_count')
                    ->label('Reservas')
                    ->counts('reservations'),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([
                Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->label('Activas'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['property']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentalProperties::route('/'),
            'create' => Pages\CreateRentalProperty::route('/create'),
            'edit' => Pages\EditRentalProperty::route('/{record}/edit'),
            'view' => Pages\ViewRentalProperty::route('/{record}'),
        ];
    }
}
