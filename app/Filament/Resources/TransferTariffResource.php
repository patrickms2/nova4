<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransferTariffResource\Pages\CreateTransferTariff;
use App\Filament\Resources\TransferTariffResource\Pages\EditTransferTariff;
use App\Filament\Resources\TransferTariffResource\Pages\ListTransferTariffs;

use App\Models\TransferTariff;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TransferTariffResource extends Resource
{
    protected static ?string $model = TransferTariff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static \UnitEnum|string|null $navigationGroup = 'Taxis';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Tarifas transfers';

    protected static ?string $modelLabel = 'Tarifa transfer';

    protected static ?string $pluralModelLabel = 'Tarifas transfers';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 24;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('origin_zone')
                ->label('Zona origen')
                ->required()
                ->maxLength(255),
            TextInput::make('destination_zone')
                ->label('Zona destino')
                ->required()
                ->maxLength(255),
            TextInput::make('price')
                ->label('Precio')
                ->numeric()
                ->required()
                ->minValue(0)
                ->prefix('€'),
            TextInput::make('currency')
                ->label('Moneda')
                ->required()
                ->maxLength(3)
                ->default('EUR'),
            TextInput::make('holiday_surcharge_percent')
                ->label('Recargo festivo %')
                ->integer()
                ->required()
                ->minValue(0)
                ->maxValue(100)
                ->default(15),
            TextInput::make('igic_percent')
                ->label('IGIC %')
                ->integer()
                ->required()
                ->minValue(0)
                ->maxValue(100)
                ->default(7),
            Toggle::make('igic_included')
                ->label('IGIC incluido'),
            Toggle::make('is_active')
                ->label('Activa')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('origin_zone')
                    ->label('Origen')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destination_zone')
                    ->label('Destino')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Precio')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('holiday_surcharge_percent')
                    ->label('Festivo %')
                    ->sortable(),
                TextColumn::make('igic_percent')
                    ->label('IGIC %')
                    ->sortable(),
                IconColumn::make('igic_included')
                    ->label('IGIC incl.')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Activa'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('origin_zone');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransferTariffs::route('/'),
            'create' => CreateTransferTariff::route('/create'),
            'edit' => EditTransferTariff::route('/{record}/edit'),
        ];
    }
}
