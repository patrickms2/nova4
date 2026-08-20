<?php

declare(strict_types=1);

namespace App\Filament\App\Facturacion\Resources\ClienteResource\Pages;

use App\Filament\App\Facturacion\Resources\ClienteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Pages\Enums\SubNavigationPosition;

final class ClientesFacturas extends ManageRelatedRecords   
{
    protected static string $relationship = 'facturas';
    protected static string $resource = ClienteResource::class;

    protected static ?string $title = 'Facturas';

    protected static ?string $recordTitleAttribute = 'codfactura';
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codfactura')
                    ->label('Nº factura')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fechaemitido')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('baseimponible')
                    ->label('Base')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('impuesto')
                    ->label('IGIC')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('importe')
                    ->label('Total')
                    ->money('EUR')
                    ->sortable(),
                IconColumn::make('pagada')
                    ->label('Pagada')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
