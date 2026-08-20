<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\RelationManagers;

use App\Filament\App\Rentals\Resources\RentalReservationResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimelineRelationManager extends RelationManager
{
    protected static string $relationship = 'timelineEvents';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('occurred_at')->label('Fecha')->dateTime('d M Y H:i'),
                TextColumn::make('event_type')->label('Tipo')->badge(),
                TextColumn::make('title')->label('Evento'),
                TextColumn::make('description')->label('Descripción')->wrap(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
