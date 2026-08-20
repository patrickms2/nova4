<?php

namespace App\Filament\App\Community\Widgets;

use App\Models\WorkOrderTask;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingWork extends TableWidget
{
    protected static ?string $heading = 'Trabajo pendiente';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => WorkOrderTask::query()->with('workOrder.community')->where('status', 'pending')->whereHas('workOrder', fn ($query) => $query->whereDate('work_date', '>=', today()))->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")->limit(8))
            ->columns([
                TextColumn::make('title')->label('Tarea')->wrap(), TextColumn::make('workOrder.code')->label('Orden'), TextColumn::make('workOrder.community.name')->label('Comunidad'), TextColumn::make('priority')->label('Prioridad')->badge(), TextColumn::make('workOrder.work_date')->label('Fecha')->date(),
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
