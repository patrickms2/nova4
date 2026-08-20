<?php

namespace App\Filament\Resources\BookingResource\RelationManagers;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
class VillaBookingRelationManager extends RelationManager
{
    protected static string $relationship = 'villaBooking';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('villa_id')
                    ->relationship('villa', 'name')->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('check_in_date'),
                Forms\Components\DatePicker::make('check_out_date'),
                Forms\Components\TextInput::make('number_of_adults')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('number_of_children')
                    ->required()
                    ->numeric(),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('villa.name')
                    ->label('Villa')->searchable(),
                Tables\Columns\TextColumn::make('check_in_date')
                    ->date(),
                Tables\Columns\TextColumn::make('check_out_date')
                    ->date(),
                Tables\Columns\TextColumn::make('number_of_adults'),
                Tables\Columns\TextColumn::make('number_of_children'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
