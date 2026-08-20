<?php

namespace App\Filament\App\Facturacion\Resources;

use App\Filament\App\Facturacion\Facturacion;
use App\Filament\App\Facturacion\Resources\FormaCobroResource\Pages;
use App\Filament\App\Facturacion\Resources\FormaCobroResource\Schemas\FormaCobroForm;
use App\Filament\App\Facturacion\Resources\FormaCobroResource\Tables\FormasCobroTable;
use App\Models\FormaCobro;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FormaCobroResource extends Resource
{
    protected static ?string $model = FormaCobro::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Formas de Cobro';

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $cluster = Facturacion::class;
    protected static ?int $navigationSort = 50;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return FormaCobroForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return FormasCobroTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormasCobro::route('/'),
            'create' => Pages\CreateFormaCobro::route('/create'),
            'edit' => Pages\EditFormaCobro::route('/{record}/edit'),
        ];
    }
}
