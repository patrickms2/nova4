<?php

namespace App\Filament\Resources\RelationManagers;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
class HotelBookingRelationManager extends RelationManager
{
    protected static string $relationship = 'hotelBooking';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('number_of_rooms')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('number_of_guests'),
                Forms\Components\DatePicker::make('check_in_date')
                    ->required(),
                Forms\Components\DatePicker::make('check_out_date'),
            ]);
    }
    public function table(Table $table): Table
{
        return $table
            ->recordTitleAttribute('HotelBookingID')
            ->columns([
                Tables\Columns\TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roomType.name')
                    ->label('Room Type'),
                Tables\Columns\TextColumn::make('check_in_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out_date'),
                Tables\Columns\TextColumn::make('number_of_rooms'),
                Tables\Columns\TextColumn::make('number_of_guests'),
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
