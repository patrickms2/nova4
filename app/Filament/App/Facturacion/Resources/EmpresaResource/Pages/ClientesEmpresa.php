<?php

declare(strict_types=1);

namespace App\Filament\App\Facturacion\Resources\EmpresaResource\Pages;

use App\Filament\App\Facturacion\Resources\ClienteResource;
use App\Filament\App\Facturacion\Resources\EmpresaResource;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Pages\Enums\SubNavigationPosition;

final class ClientesEmpresa extends ManageRelatedRecords   
{
    protected static string $relationship = 'clientes';
    protected static string $resource = EmpresaResource::class;
    protected string $view = 'filament.app.resources.facturacion.resources.empresas.pages.clientes-empresa';

    protected static ?string $title = 'Clientes';
    protected static ?string $navigationParentItem = 'Empresas';

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public function form(Schema $schema): Schema
    {
        return ClienteResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ClienteResource::table($table);

    }
}
