<?php

namespace App\Filament\TourAdmin\Resources;

use Filament\Support\Icons\Heroicon;

use App\Models\Tour;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|\UnitEnum|null $navigationGroup = 'Tours';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tour Information')
                    ->schema([
                        Forms\Components\TextInput::make('tour_name')
                            ->label('Tour Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('short_description')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Capacity and Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->numeric()
                            ->prefix('EUR'),
                        Forms\Components\TextInput::make('discount_percentage')
                            ->default(0)
                            ->suffix('%'),
                        Forms\Components\TextInput::make('max_capacity')
                            ->numeric(),
                        Forms\Components\TextInput::make('min_participants')
                            ->default(1)
                            ->numeric(),
                    ]),
                Section::make('Details')
                    ->schema([
                        Forms\Components\TextInput::make('duration_hours')
                            ->suffix('hours'),
                        Forms\Components\TextInput::make('duration_days')
                            ->suffix('days'),
                        Forms\Components\Select::make('difficulty_level')
                            ->options([
                                'Easy' => 'Easy',
                                'Moderate' => 'Moderate',
                                'Difficult' => 'Difficult',
                            ])
                            ->default('Easy'),
                    ])
                    ->columns(3),
                Section::make('Media and Status')
                    ->schema([
                        Forms\Components\FileUpload::make('main_image_url')
                            ->label('Main Image')
                            ->image()
                            ->directory('tour-images')->maxSize(2048),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured'),
                    ]),
                Section::make('External Source')
                    ->schema([
                    Forms\Components\Select::make('server_id')->relationship('server', 'name')->required()->searchable()->preload(),
                    Forms\Components\Select::make('external_source_id')->relationship('externalSource', 'name')->required()->searchable()->preload(),

                Tables\Columns\TextColumn::make('externalSource.resource_type')
                    ->label('Resource')
                    ->badge()
                    ->sortable(),


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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tour_name')
                    ->label('Tour')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('externalSyncMappings.source_label')
                    ->label('Source')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('externalSyncMappings.resource_type')
                    ->label('Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('max_capacity')
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->relationship('externalSyncMappings', 'source_label')->searchable(),
                Tables\Filters\SelectFilter::make('resource_type')
                    ->relationship('externalSyncMappings', 'resource_type')->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => TourResource\Pages\ListTours::route('/'),
            'create' => TourResource\Pages\CreateTour::route('/create'),
            'edit' => TourResource\Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
