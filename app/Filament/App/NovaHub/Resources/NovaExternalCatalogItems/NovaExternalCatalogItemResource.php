<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems;

use App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\Pages\ListNovaExternalCatalogItems;
use App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\Pages\ViewNovaExternalCatalogItem;
use App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\Tables\NovaExternalCatalogItemsTable;
use App\Models\NovaExternalCatalogItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NovaExternalCatalogItemResource extends Resource
{
    protected static ?string $model = NovaExternalCatalogItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Nova Hub';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Exportaciones';

    protected static ?string $modelLabel = 'Exportación';

    protected static ?string $pluralModelLabel = 'Exportaciones';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 21;

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return NovaExternalCatalogItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaExternalCatalogItems::route('/'),
            'view' => ViewNovaExternalCatalogItem::route('/{record}'),
        ];
    }
}
