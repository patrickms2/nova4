<?php

namespace App\Filament\App\Community\Widgets;

use App\Models\WorkOrder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TodayServices extends TableWidget
{
    protected static ?string $heading = 'Servicios de hoy';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => WorkOrder::query()->with(['community', 'starter'])->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed')])->whereDate('work_date', today())->orderBy('id'))
            ->columns([
                TextColumn::make('code')->label('Orden'), TextColumn::make('community.name')->label('Comunidad'), TextColumn::make('starter.name')->label('Empleado')->placeholder('Sin iniciar'), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('progress')->label('Progreso')->state(fn (WorkOrder $record): string => $record->completed_tasks_count.'/'.$record->tasks_count),
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
