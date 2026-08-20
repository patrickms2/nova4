<?php

namespace App\Filament\App\Community\Resources\Communities\RelationManagers;

use App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrders';

    protected static ?string $title = 'Órdenes de trabajo';

    public function table(Table $table): Table
    {
        return $table->defaultSort('work_date', 'desc')->columns([
            TextColumn::make('code')->label('Código')->searchable(),
            TextColumn::make('work_date')->label('Fecha')->date(),
            TextColumn::make('plan.name')->label('Plan')->placeholder('Manual'),
            TextColumn::make('tasks_count')->counts('tasks')->label('Tareas'),
            TextColumn::make('incidents_count')->counts('incidents')->label('Incidencias')->badge(),
            TextColumn::make('status')->label('Estado')->badge(),
        ])->recordUrl(fn (WorkOrder $record): string => WorkOrderResource::getUrl('view', ['record' => $record]));
    }
}
