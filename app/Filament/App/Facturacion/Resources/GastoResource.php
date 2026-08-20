<?php

namespace App\Filament\App\Facturacion\Resources;

use App\Filament\App\Facturacion\Facturacion;
use App\Filament\App\Facturacion\Resources\GastoResource\Pages;
use App\Filament\App\Facturacion\Resources\GastoResource\Schemas\GastoForm;
use App\Filament\App\Facturacion\Resources\GastoResource\Tables\GastosTable;
use App\Models\Gasto;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GastoResource extends Resource
{
    protected static ?string $model = Gasto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Gastos';

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $cluster = Facturacion::class;
    protected static ?int $navigationSort = 45;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return GastoForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return GastosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGastos::route('/'),
            'create' => Pages\CreateGasto::route('/create'),
            'edit' => Pages\EditGasto::route('/{record}/edit'),
        ];
    }
}
