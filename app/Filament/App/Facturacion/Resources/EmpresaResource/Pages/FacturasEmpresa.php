<?php

declare(strict_types=1);

namespace App\Filament\App\Facturacion\Resources\EmpresaResource\Pages;
use App\Filament\App\Facturacion\Resources\FacturaResource;
use App\Filament\App\Facturacion\Resources\EmpresaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Pages\Enums\SubNavigationPosition;

final class FacturasEmpresa extends ManageRelatedRecords   
{
    protected static string $relationship = 'facturas';
    protected static string $resource = EmpresaResource::class;
    protected string $view = 'filament.app.resources.facturacion.resources.empresas.pages.facturas-empresa';

    protected static ?string $title = 'Facturas';

    protected static ?string $recordTitleAttribute = 'codfactura';
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $navigationParentItem = 'Empresas';


    public function form(Schema $form): Schema
    {
        return FacturaResource::form($form);
    }

    public function table(Table $table): Table
    {
        return FacturaResource::table($table);
    }
}
