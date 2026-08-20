<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalOrders;

use App\Filament\App\NovaHub\Resources\NovaExternalOrders\Pages\ListNovaExternalOrders;
use App\Filament\App\NovaHub\Resources\NovaExternalOrders\Pages\ViewNovaExternalOrder;
use App\Filament\App\NovaHub\Resources\NovaExternalOrders\Tables\NovaExternalOrdersTable;
use App\Models\NovaExternalOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NovaExternalOrderResource extends Resource
{
    protected static ?string $model = NovaExternalOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static \UnitEnum|string|null $navigationGroup = 'Nova Hub';

    protected static ?string $navigationLabel = 'Pedidos externos';

    protected static ?string $modelLabel = 'Pedido externo';

    protected static ?string $pluralModelLabel = 'Pedidos externos';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 22;

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return NovaExternalOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaExternalOrders::route('/'),
            'view' => ViewNovaExternalOrder::route('/{record}'),
        ];
    }
}
