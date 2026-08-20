<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\TaxiServiceResource\Pages;
use App\Filament\Resources\TaxiServiceResource\Relations;
use App\Models\TaxiService;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class TaxiServiceResource extends Resource
{
    protected static ?string $model = TaxiService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Taxis';
    protected static ?string $navigationParentGroup = 'Catálogo';
    protected static ?string $navigationLabel = 'Servicios Taxi';
    protected static ?int $navigationSort = 20;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Taxi Service Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('average_rating')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->maxValue(5)
                            ->minValue(0)
                            ->step(0.01),
                        Forms\Components\TextInput::make('total_ratings')
                            ->disabled(),
                    ])->columns(2),
                Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('logo_url')
                            ->label('Logo URL'),
                        Forms\Components\TextInput::make('website')
                            ->url(),
                        Forms\Components\TextInput::make('phone')
                            ->tel(),
                        Forms\Components\TextInput::make('email')
                            ->email(),
                    ]),
                Section::make('status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Select::make('manager_id')
                            ->label('Manager')
                            ->relationship('manager', 'name')
                            ->searchable(),
                    ]),
                Section::make('External Source')
                    ->schema([
                        TextEntry::make('source_label')
                            ->label('Source')
                            ->state(fn ($record): string => (string) ($record?->externalSyncMappings()->latest()->value('source_label') ?? 'Local')),
                        TextEntry::make('resource_type')
                            ->label('Type')
                            ->state(fn ($record): string => (string) ($record?->externalSyncMappings()->latest()->value('resource_type') ?? 'local')),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('externalSyncMappings.source_label')
                    ->label('Source')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('externalSyncMappings.resource_type')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location'),
                Tables\Columns\TextColumn::make('average_rating')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('IsActive')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('source')
                    ->relationship('externalSyncMappings', 'source_label')->searchable(),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),

                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            Relations\ListTaxiServiceVehicles::make(),
            Relations\ListTaxiServiceDrivers::make(),
            Relations\ListTaxiServiceBookings::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxiServices::route('/'),
            'create' => Pages\CreateTaxiService::route('/create'),
            'view' => Pages\ViewTaxiService::route('/{record}'),
            'edit' => Pages\EditTaxiService::route('/{record}/edit'),
        ];
    }
}
