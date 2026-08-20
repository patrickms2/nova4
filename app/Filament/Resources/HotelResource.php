<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\HotelResource\Pages;
use App\Models\Hotel;
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

class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Hoteles';
    protected static ?string $navigationParentGroup = 'Catálogo';
    protected static ?string $navigationLabel = 'Hoteles';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Hotel Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Hotel Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tariff_zone')
                            ->label('Tariff Zone')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->required(),
                        Forms\Components\Select::make('star_rating')
                            ->options([
                                1 => '1 Star',
                                2 => '2 Stars',
                                3 => '3 Stars',
                                4 => '4 Stars',
                                5 => '5 Stars',
                            ])
                            ->preload(),
                        Forms\Components\TimePicker::make('check_in_time')
                            ->label('Check-in Time'),
                        Forms\Components\TimePicker::make('check_out_time')
                            ->label('Check-out Time'),
                        Forms\Components\TextInput::make('average_rating')
                            ->label('Average Rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.01)
                            ->disabled(),
                        Forms\Components\TextInput::make('total_ratings')
                            ->label('Total Ratings')
                            ->numeric()
                            ->minValue(0)
                            ->disabled(),
                    ])->columns(2),

                Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Select::make('manager_id')
                            ->label('Manager')
                            ->preload()
                            ->relationship('manager', 'email')
                            ->searchable(),
                    ])->columns(2),

                Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('main_image_url')
                            ->label('Main Image')
                            ->image()
                            ->directory('hotel-images')->maxSize(2048),
                    ]),

                Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured'),
                    ])->columns(2),

                Section::make('External Source')
                    ->schema([
                        TextEntry::make('source_label')
                            ->label('Source')
                            ->state(fn ($record): string => (string) ($record?->externalSyncMappings()->latest()->value('source_label') ?? 'Local')),
                        TextEntry::make('resource_type')
                            ->label('Type')
                            ->state(fn ($record): string => (string) ($record?->externalSyncMappings()->latest()->value('resource_type') ?? 'local')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Hotel Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('externalSyncMappings.source_label')
                    ->label('Source')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('externalSyncMappings.resource_type')
                    ->label('Type')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tariff_zone')
                    ->label('Location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('star_rating')
                    ->label('Stars')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Rating')
                    ->sortable(),
                Tables\Columns\TextColumn::make('manager.email')
                    ->label('Manager')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')->searchable(),
                Tables\Filters\SelectFilter::make('star_rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
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
        return [
            'index' => Pages\ListHotels::route('/'),
            // 'create' => Pages\CreateHotel::route('/create'),
            // 'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
