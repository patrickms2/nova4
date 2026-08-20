<?php

namespace App\Filament\App\Facturacion\Resources;

use App\Filament\App\Facturacion\Resources\ClienteResource\Pages;
use App\Filament\App\Facturacion\Resources\ClienteResource\RelationManagers\FacturasRelationManager;
use App\Filament\App\Facturacion\Resources\ClienteResource\Schemas\ClienteForm;
use App\Filament\App\Facturacion\Resources\ClienteResource\Tables\ClientesTable;
use App\Filament\App\Facturacion\Resources\ClienteResource\Pages\ClientesFacturas;
use Filament\Pages\Page;
use App\Models\Cliente;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use App\Filament\App\Facturacion\Facturacion;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $cluster = Facturacion::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?int $navigationSort = 30;

    protected static bool $isScopedToTenant = false;
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    public static function form(Form $form): Form
    {
        return ClienteForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return ClientesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FacturasRelationManager::class,
        ];
    }
public static function getRecordSubNavigation(Page $page): array
    {
        $record = $page->getRecord();

        $pages = [
                ClientesFacturas::class,

        ];




        return $page->generateNavigationItems($pages);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
            'facturas' => Pages\ClientesFacturas::route('/{record}/facturas'),
        ];
    }
}
