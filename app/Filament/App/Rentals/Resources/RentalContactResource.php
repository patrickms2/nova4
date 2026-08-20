<?php

namespace App\Filament\App\Rentals\Resources;

use App\Filament\App\Rentals\Rentals;
use App\Filament\App\Rentals\Resources\RentalContactResource\Pages;
use App\Filament\App\Rentals\Resources\RentalContactResource\Widgets;
use App\Models\RentalContact;
use App\Models\RentalProperty;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RentalContactResource extends Resource
{
    protected static ?string $model = RentalContact::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Contactos';

    protected static ?string $pluralModelLabel = 'Contactos';

    protected static ?string $modelLabel = 'Contacto';

    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';

    protected static ?string $cluster = Rentals::class;

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contacto')
                    ->schema([
                        Select::make('rental_property_id')
                            ->label('Propiedad')
                            ->options(fn () => RentalProperty::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('category')
                            ->label('Categoría')
                            ->options(RentalContact::categories())
                            ->required(),
                        Select::make('workCategories')
                            ->label('Tipos de trabajo')
                            ->relationship('workCategories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Servicios que puede prestar este contacto.')
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Dirección')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RentalContact::categories()[$state] ?? $state),
                TextColumn::make('workCategories.name')->label('Tipos de trabajo')->badge(),
                TextColumn::make('phone')
                    ->label('Teléfono'),
                TextColumn::make('email')
                    ->label('Email'),
                TextColumn::make('rentalProperty.name')
                    ->label('Propiedad')
                    ->default('Sin propiedad'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(RentalContact::categories()),
                SelectFilter::make('rental_property_id')
                    ->label('Propiedad')
                    ->options(fn () => RentalProperty::query()->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo contacto')
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
        return parent::getEloquentQuery()->with(['rentalProperty', 'workCategories']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentalContacts::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\RentalContactStats::class,
        ];
    }
}
