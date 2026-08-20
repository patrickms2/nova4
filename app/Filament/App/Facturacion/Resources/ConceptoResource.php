<?php

namespace App\Filament\App\Facturacion\Resources;

use App\Filament\App\Facturacion\Resources\ConceptoResource\Pages;
use App\Filament\App\Facturacion\Resources\ConceptoResource\Schemas\ConceptoForm;
use App\Filament\App\Facturacion\Resources\ConceptoResource\Tables\ConceptosTable;
use App\Models\Concepto;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use App\Filament\App\Facturacion\Facturacion;

class ConceptoResource extends Resource
{
    protected static ?string $model = Concepto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Conceptos';

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?int $navigationSort = 40;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $cluster = Facturacion::class;
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return ConceptoForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return ConceptosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConceptos::route('/'),
            'create' => Pages\CreateConcepto::route('/create'),
            'edit' => Pages\EditConcepto::route('/{record}/edit'),
        ];
    }
}
