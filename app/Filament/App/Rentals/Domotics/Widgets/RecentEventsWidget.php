<?php

namespace App\Filament\App\Rentals\Domotics\Widgets;

use App\Models\DomoticsEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentEventsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(DomoticsEvent::query()->latest()->limit(10))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime(),
                TextColumn::make('event_type')
                    ->label('Evento')->searchable()
                    ->badge(),
                TextColumn::make('accessPoint.name')
                    ->label('Punto de acceso')->searchable(),
                TextColumn::make('accessGrant.pin')
                    ->label('PIN'),
                TextColumn::make('user.email')
                    ->label('Usuario'),
            ])
            ->paginated(false);
    }
}
