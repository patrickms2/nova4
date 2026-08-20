<?php

namespace App\Filament\App\Rentals\Resources;

use App\Filament\App\Rentals\Resources\RentalInventoryItemResource\Pages;
use App\Models\RentalInventoryItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\App\Rentals\Rentals;

class RentalInventoryItemResource extends Resource
{
    protected static ?string $model = RentalInventoryItem::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $pluralModelLabel = 'Inventario';
    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';
    protected static ?string $cluster = Rentals::class;
    protected static ?string $modelLabel = 'Bien';


    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->schema([
                        Select::make('rental_property_id')
                            ->label('Propiedad')
                            ->relationship('rentalProperty', 'name')
                            ->required(),
                        Select::make('category')
                            ->label('Categoría')
                            ->options(self::categories())
                            ->required(),
                        TextInput::make('location')
                            ->label('Ubicación')
                            ->required(),
                        TextInput::make('brand')
                            ->label('Marca')
                            ->nullable(),
                        TextInput::make('model')
                            ->label('Modelo')
                            ->nullable(),
                        TextInput::make('serial_number')
                            ->label('Número de serie')
                            ->nullable(),
                        Textarea::make('qr_code')
                            ->label('Código QR / URL')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->columns(2),
                Section::make('Compra y garantía')
                    ->schema([
                        TextInput::make('purchase_value')
                            ->label('Valor de compra')
                            ->numeric()
                            ->prefix('€')
                            ->default(0),
                        DatePicker::make('purchase_date')
                            ->label('Fecha de compra'),
                        DatePicker::make('warranty_expires_at')
                            ->label('Fin de garantía'),
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'good' => 'Buen estado',
                                'damaged' => 'Dañado',
                                'missing' => 'Falta',
                                'replaced' => 'Reemplazado',
                            ])
                            ->default('good'),
                    ])
                    ->columns(2),
                Section::make('Evidencia')
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Foto')
                            ->image()
                            ->directory('rental-inventory/photos'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::categories()[$state] ?? $state),
                TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable(),
                TextColumn::make('brand')
                    ->label('Marca'),
                TextColumn::make('model')
                    ->label('Modelo'),
                TextColumn::make('purchase_value')
                    ->label('Valor')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('warranty_expires_at')
                    ->label('Garantía')
                    ->date('d M Y'),
                IconColumn::make('isUnderWarranty')
                    ->label('En garantía')
                    ->boolean()
                    ->getStateUsing(fn (RentalInventoryItem $record): bool => $record->isUnderWarranty()),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(self::categories()),
                SelectFilter::make('status')
                    ->options([
                        'good' => 'Buen estado',
                        'damaged' => 'Dañado',
                        'missing' => 'Falta',
                        'replaced' => 'Reemplazado',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Crear bien')
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
            'index' => Pages\ListRentalInventoryItems::route('/'),
            'view' => Pages\ViewRentalInventoryItem::route('/{record}'),
        ];
    }

    public static function categories(): array
    {
        return [
            'fachada' => 'Fachada',
            'jardin' => 'Jardín',
            'piscina' => 'Piscina',
            'jacuzzi' => 'Jacuzzi',
            'salon' => 'Salón',
            'cocina' => 'Cocina',
            'dormitorio' => 'Dormitorio',
            'bano' => 'Baño',
            'terraza' => 'Terraza',
            'barbacoa' => 'Barbacoa',
            'instalaciones' => 'Cuarto de instalaciones',
            'television' => 'Televisión',
            'frigorifico' => 'Frigorífico',
            'lavadora' => 'Lavadora',
            'secadora' => 'Secadora',
            'lavavajillas' => 'Lavavajillas',
            'horno' => 'Horno',
            'microondas' => 'Microondas',
            'cafetera' => 'Cafetera',
            'aire_acondicionado' => 'Aire acondicionado',
            'mobiliario' => 'Mobiliario',
            'decoracion' => 'Decoración',
            'otro' => 'Otro',
        ];
    }
}
