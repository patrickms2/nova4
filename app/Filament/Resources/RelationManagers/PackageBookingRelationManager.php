<?php

namespace App\Filament\Resources\RelationManagers;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
class PackageBookingRelationManager extends RelationManager
{
    protected static string $relationship = 'packageBooking';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('package_id')
                    ->relationship('package', 'name')->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('start_date'),
                Forms\Components\TextInput::make('number_of_adults')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('number_of_children')
                    ->numeric()
                    ->default(0),
            ]);
    }
    public function table(Table $table): Table
{
        return $table
            ->recordTitleAttribute('PackageBookingID')
            ->columns([
                Tables\Columns\TextColumn::make('package.name')
                    ->label('Travel Package')->searchable(),
                Tables\Columns\TextColumn::make('start_date')
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
