<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\RelationManagers;

use App\Filament\App\Rentals\Resources\RentalReservationResource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    public function form(Form $form): Form
    {
        return $form
            ->components([
                TextInput::make('type')->required(),
                TextInput::make('label')->required(),
                TextInput::make('amount')->numeric()->prefix('€')->required(),
                TextInput::make('provider_name')->label('Proveedor'),
                Toggle::make('generates_commission')->label('Genera comisión'),
                Toggle::make('is_income')->label('Ingreso'),
                Toggle::make('is_expense')->label('Gasto'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label')->label('Concepto'),
                TextColumn::make('type')->label('Tipo'),
                TextColumn::make('amount')->label('Importe')->money('EUR'),
                IconColumn::make('is_income')->label('Ingreso')->boolean(),
                IconColumn::make('is_expense')->label('Gasto')->boolean(),
                TextColumn::make('provider_name')->label('Proveedor'),
            ])
            ->defaultSort('sort_order');
    }
}
