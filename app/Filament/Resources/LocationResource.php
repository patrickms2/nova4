<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 60;

    protected static string|UnitEnum|null $navigationGroup = 'Locations';
    protected static ?string $navigationParentGroup = 'Catálogo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Location Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Location Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->label('Address'),
                        Forms\Components\Select::make('city_id')
                            ->label('City')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90)
                            ->step(0.0000001),
                        Forms\Components\TextInput::make('longitude')
                            ->minValue(-180)
                            ->maxValue(180),
                        Forms\Components\Toggle::make('is_popular')
                            ->label('Popular Location')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Location Name'),
                Tables\Columns\TextColumn::make('address')
                    ->label('Address'),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('City'),
                Tables\Columns\TextColumn::make('latitude')
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude'),
                Tables\Columns\IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city_id')
                    ->relationship('city', 'name')->searchable(),
                Tables\Filters\TernaryFilter::make('is_popular')
                    ->label('Popular Locations'),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
