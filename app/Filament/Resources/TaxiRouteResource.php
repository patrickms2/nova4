<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\TaxiRouteResource\Pages;
use App\Models\ExternalCatalogItem;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TaxiRouteResource extends Resource
{
    protected static ?string $model = ExternalCatalogItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Taxis';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Rutas Taxi';

    protected static ?string $modelLabel = 'Ruta';

    protected static ?string $pluralModelLabel = 'Rutas';

    protected static ?int $navigationSort = 7;

    public static function form(Form $schema): Form
    {
        return $schema->schema([
            Schemas\Components\Section::make('Ruta')
                ->schema([
                    Forms\Components\TextInput::make('name'),
                    Forms\Components\TextInput::make('sku'),
                    Forms\Components\TextInput::make('price')->numeric(),
                    Forms\Components\TextInput::make('currency')->maxLength(3)->disabled(),
                    Forms\Components\TextInput::make('status'),
                    Forms\Components\TextInput::make('source_label')->disabled(),
                    Forms\Components\TextInput::make('source_platform')->disabled(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ruta')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('external_id')->label('External ID')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('externalSource.resource_type')->label('Resource')->badge()->sortable(),
                Tables\Columns\TextColumn::make('price')->money(fn (ExternalCatalogItem $record): string => $record->currency ?: 'EUR')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('source_label')->label('Source')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('business_name')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_synced_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_name')->options(fn (): array => ExternalCatalogItem::query()->distinct()->pluck('business_name', 'business_name')->filter()->all()),
                Tables\Filters\SelectFilter::make('source_platform')->options(fn (): array => ExternalCatalogItem::query()->distinct()->pluck('source_platform', 'source_platform')->all()),
                Tables\Filters\SelectFilter::make('source_label')->options(fn (): array => ExternalCatalogItem::query()->distinct()->pluck('source_label', 'source_label')->all()),
                Tables\Filters\SelectFilter::make('status')->options(fn (): array => ExternalCatalogItem::query()->distinct()->pluck('status', 'status')->filter()->all()),
            ])
            ->actions([
                Actions\EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query): void {
                $query
                    ->where('source_platform', 'woo')
                    ->orWhereHas('externalSource', function (Builder $query): void {
                        $query->whereIn('resource_type', ['tour_route', 'taxi_route', 'route', 'routes']);
                    })
                    ->orWhere('type', 'route')
                    ->orWhere('metadata', 'like', '%tour_route%')
                    ->orWhere('metadata', 'like', '%taxi_route%');
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxiRoutes::route('/'),
            'edit' => Pages\EditTaxiRoute::route('/{record}/edit'),
        ];
    }
}
