<?php

namespace App\Filament\App\Facturacion\Resources;

use App\Filament\App\Facturacion\Facturacion;
use App\Filament\App\Facturacion\Resources\FacturaResource\Pages;
use App\Filament\App\Facturacion\Resources\FacturaResource\RelationManagers\RegistrosRelationManager;
use App\Filament\App\Facturacion\Resources\FacturaResource\Schemas\FacturaForm;
use App\Filament\App\Facturacion\Resources\FacturaResource\Tables\FacturasTable;
use App\Filament\App\Facturacion\Resources\FacturaResource\Widgets\FacturasStats;
use App\Models\Factura;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FacturaResource extends Resource
{
    protected static ?string $model = Factura::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Facturas';

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $cluster = Facturacion::class;

    protected static bool $isScopedToTenant = false;


    public static function form(Form $form): Form
    {
        return FacturaForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return FacturasTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\InvoicesStatsWidget::class,
        ];
    }

    public static function getRelations(): array
    {
        return [
            RegistrosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacturas::route('/'),
            'create' => Pages\CreateFactura::route('/create'),
            'edit' => Pages\EditFactura::route('/{record}/edit'),
        ];
    }
}
