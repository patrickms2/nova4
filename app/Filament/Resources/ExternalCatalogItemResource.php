<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\ExternalCatalogItemResource\Pages;
use App\Models\ExternalCatalogItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ExternalCatalogItemResource extends Resource
{
    protected static ?string $model = ExternalCatalogItem::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Productos';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Productos Importados';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $schema): Form
    {
        return $schema->schema([
            Schemas\Components\Section::make('Catalog Item')
                ->schema([
                    Forms\Components\Select::make('server_id')->relationship('server', 'name')->required()->searchable()->preload(),
                    Forms\Components\Select::make('external_source_id')->relationship('externalSource', 'source_label')->required()->searchable()->preload(),
                    Forms\Components\TextInput::make('business_name'),
                    Forms\Components\TextInput::make('source_platform')->required(),
                    Forms\Components\TextInput::make('source_label')->required(),
                    Forms\Components\TextInput::make('external_id')->required(),
                    Forms\Components\TextInput::make('type')->required(),
                    Forms\Components\TextInput::make('status'),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('sku'),
                    Forms\Components\TextInput::make('price')->numeric(),
                    Forms\Components\TextInput::make('currency')->maxLength(3),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('externalSource.resource_type')
                    ->label('Resource')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('externalSource.target_model')
                    ->label('Model'),
                Tables\Columns\TextColumn::make('metadata.resource_type')
                    ->label('Resource type')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')->money(fn (ExternalCatalogItem $record): string => $record->currency ?: 'EUR')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('source_label')->label('Source')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('business_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('source_platform')->badge()->sortable(),
                Tables\Columns\TextColumn::make('server.name')->label('Server')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('last_synced_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_name')->options(fn (): array => ExternalCatalogItem::query()->distinct()->pluck('business_name', 'business_name')->filter()->all()),
                Tables\Filters\SelectFilter::make('source_platform')->options(fn (): array => ExternalCatalogItem::query()->distinct()->pluck('source_platform', 'source_platform')->all()),
                Tables\Filters\SelectFilter::make('source_label')->options(fn (): array => ExternalCatalogItem::query()->distinct()->pluck('source_label', 'source_label')->all()),
                Tables\Filters\SelectFilter::make('server')->relationship('server', 'name')->searchable(),
                Tables\Filters\SelectFilter::make('resource_type')
                    ->relationship('externalSource', 'resource_type')->searchable(),
                Tables\Filters\SelectFilter::make('target_model')
                    ->relationship('externalSource', 'target_model')->searchable(),
                Tables\Filters\SelectFilter::make('type')->options(fn (): array => ExternalCatalogItem::query()->distinct()->pluck('type', 'type')->all()),
                Tables\Filters\SelectFilter::make('status')->options(fn (): array => ExternalCatalogItem::query()->distinct()->pluck('status', 'status')->filter()->all()),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExternalCatalogItems::route('/'),
            'create' => Pages\CreateExternalCatalogItem::route('/create'),
            'edit' => Pages\EditExternalCatalogItem::route('/{record}/edit'),
        ];
    }
}
