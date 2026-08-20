<?php

namespace App\Filament\App\Facturacion\Resources\FacturaResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegistrosRelationManager extends RelationManager
{
    protected static string $relationship = 'registros';

    protected static ?string $title = 'Líneas de factura';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descripcion')->required()->columnSpanFull(),
                TextInput::make('unidad')->maxLength(20),
                TextInput::make('cantidad')->numeric()->default(1)->live(),
                TextInput::make('precio')->numeric()->live(),
                TextInput::make('descuento')->numeric(),
                TextInput::make('impuesto')->numeric(),
                TextInput::make('retenciones')->numeric(),
                TextInput::make('importe')->numeric(),
                DatePicker::make('fecha'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')->date('d/m/Y'),
                TextColumn::make('concepto.concepto')->wrap()->searchable(),
                TextColumn::make('descripcion')->wrap(),
                TextColumn::make('cantidad')->label('Cantidad'),
                TextColumn::make('concepto.unidad')->label('Unidad'),
                TextColumn::make('descuento'),
                TextColumn::make('impuesto'),
                TextColumn::make('retenciones'),
                TextColumn::make('precio'),
                TextColumn::make('importe'),

            ])
            ->headerActions([
                //CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
