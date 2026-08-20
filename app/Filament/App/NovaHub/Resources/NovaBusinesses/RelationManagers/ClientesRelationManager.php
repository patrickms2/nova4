<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ClientesRelationManager extends RelationManager
{
    protected static string $relationship = 'clientes';

    protected static ?string $title = 'Clientes de facturación';

    protected static ?string $recordTitleAttribute = 'nombretotal';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codcliente')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nombretotal')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label('Teléfono'),
                TextColumn::make('facturas_count')
                    ->label('Facturas')
                    ->counts('facturas')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
