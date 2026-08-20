<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class FacturasRelationManager extends RelationManager
{
    protected static string $relationship = 'facturas';

    protected static ?string $title = 'Facturas';

    protected static ?string $recordTitleAttribute = 'codfactura';

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
                TextColumn::make('cliente.nombretotal')
                    ->label('Cliente')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('fechaemitido')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('notas')
                    ->label('Notas')
                    ->wrap(),
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
