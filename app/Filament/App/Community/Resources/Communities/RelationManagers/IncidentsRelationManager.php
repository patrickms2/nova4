<?php

namespace App\Filament\App\Community\Resources\Communities\RelationManagers;

use App\Filament\App\Community\Resources\Incidents\IncidentResource;
use App\Models\Incident;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncidentsRelationManager extends RelationManager
{
    protected static string $relationship = 'incidents';

    protected static ?string $title = 'Incidencias';

    public function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('title')->label('Incidencia')->searchable()->wrap(),
            TextColumn::make('workOrder.code')->label('Orden')->placeholder('Sin orden'),
            TextColumn::make('priority')->label('Prioridad')->badge(),
            TextColumn::make('status')->label('Estado')->badge(),
            TextColumn::make('created_at')->label('Registrada')->dateTime(),
        ])->recordUrl(fn (Incident $record): string => IncidentResource::getUrl('view', ['record' => $record]));
    }
}
