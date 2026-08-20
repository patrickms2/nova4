<?php

namespace App\Filament\App\Community\Resources\Communities\RelationManagers;

use App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrderTask;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrderTasks';

    protected static ?string $title = 'Tareas';

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('title')->label('Tarea')->searchable()->wrap(),
            TextColumn::make('workOrder.code')->label('Orden'),
            TextColumn::make('workOrder.work_date')->label('Fecha')->date(),
            TextColumn::make('priority')->label('Prioridad')->badge(),
            TextColumn::make('status')->label('Estado')->badge(),
        ])->recordUrl(fn (WorkOrderTask $record): string => WorkOrderResource::getUrl('view', ['record' => $record->work_order_id]));
    }
}
