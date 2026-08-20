<?php

namespace App\Filament\App\Facturacion\Resources;

use App\Filament\App\Facturacion\Resources\EmpresaResource\Pages;
use App\Filament\App\Facturacion\Resources\EmpresaResource\Schemas\EmpresaForm;
use App\Filament\App\Facturacion\Resources\EmpresaResource\Tables\EmpresasTable;
use App\Models\Empresa;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use Filament\Pages\Page;
use App\Filament\App\Facturacion\Resources\EmpresaResource\Pages\ClientesEmpresa;
use App\Filament\App\Facturacion\Resources\EmpresaResource\Pages\FacturasEmpresa;
use App\Filament\App\Facturacion\Facturacion;

class EmpresaResource extends Resource
{
    protected static ?string $model = Empresa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
    protected static ?string $navigationLabel = 'Empresas';

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';
    protected static ?string $navigationParentGroup = 'Nova Hub';
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $cluster = Facturacion::class;
    protected static ?int $navigationSort = 25;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return EmpresaForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return EmpresasTable::configure($table);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $record = $page->getRecord();

        $pages = [
                ClientesEmpresa::class,
                FacturasEmpresa::class,
        ];


        return $page->generateNavigationItems($pages);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmpresas::route('/'),
            'create' => Pages\CreateEmpresa::route('/create'),
            'edit' => Pages\EditEmpresa::route('/{record}/edit'),
            'clientes' =>Pages\ClientesEmpresa::route('/{record}/clientes'),
            'facturas' => Pages\FacturasEmpresa::route('/{record}/facturas'),
        ];
    }
}
