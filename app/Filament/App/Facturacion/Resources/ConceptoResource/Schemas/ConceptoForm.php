<?php

namespace App\Filament\App\Facturacion\Resources\ConceptoResource\Schemas;

use App\Models\Concepto;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use App\Models\Cliente;

class ConceptoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('codconcepto')
                    ->label('Código')
                    ->maxLength(20)
                    ->required(),
  Select::make('cliente_id')
                      ->options(Cliente::all()->pluck('nombretotal', 'id')  )
                                ->searchable()
                                ->required()
                                ->preload()
                                ->columnSpan(1)
                                ->createOptionForm([
                                    TextInput::make('cliente.nombretotal')
                                        ->label('Nombre')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('cliente.email')
                                        ->label('Email')
                                        ->required()
                                        ->email()
                                        ->maxLength(255)
                                        ->unique(),
                                    TextInput::make('cliente.telefono')
                                        ->label('Teléfono')
                                        ->maxLength(255),
                                ])
                                ->createOptionAction(fn (Action $action) => $action
                                    ->modalHeading('Crear cliente')
                                    ->modalSubmitActionLabel('Nuevo Cliente')
                                    ->modalWidth('lg')),
                TextInput::make('concepto')
                    ->label('Concepto')
                    ->maxLength(100)
                    ->required()
                    ->columnSpanFull(),
                Select::make('categoria')
                    ->label('Categoría')
                    ->options(Concepto::categorias())
                    ->nullable(),
                TextInput::make('grupo')
                    ->maxLength(40),
                TextInput::make('unidad')
                    ->maxLength(20)
                    ->default('UNID'),
                TextInput::make('precio')
                    ->numeric()
                    ->default(0),
                TextInput::make('descuento')
                    ->numeric()
                    ->default(0),
                TextInput::make('impuesto')
                    ->numeric()
                    ->default(7)
                    ->helperText('IGIC por defecto 7%'),
                TextInput::make('retenciones')
                    ->numeric()
                    ->default(15)
                    ->helperText('Retención por defecto 15%'),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
