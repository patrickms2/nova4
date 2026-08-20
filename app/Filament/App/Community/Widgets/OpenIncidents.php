<?php

namespace App\Filament\App\Community\Widgets;

use App\Models\Incident;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OpenIncidents extends TableWidget
{
    protected static ?string $heading = 'Incidencias que requieren atención';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Incident::query()->with(['community', 'workOrder'])->whereNotIn('status', ['resolved', 'closed'])->latest()->limit(8))
            ->columns([
                TextColumn::make('title')->label('Incidencia')->wrap(), TextColumn::make('community.name')->label('Comunidad'), TextColumn::make('priority')->label('Prioridad')->badge(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('created_at')->label('Antigüedad')->since(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ]);
    }
}
