<?php

namespace App\Filament\App\Community\Resources\Communities\RelationManagers;

use App\Filament\App\Community\Actions\GeneratePlanWorkOrdersAction;
use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use App\Models\CommunityPlan;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansRelationManager extends RelationManager
{
    protected static string $relationship = 'plans';

    protected static ?string $title = 'Planes de mantenimiento';

    public function table(Table $table): Table
    {
        return $table->defaultSort('valid_from', 'desc')->columns([
            TextColumn::make('name')->label('Plan')->searchable(),
            TextColumn::make('valid_from')->label('Desde')->date(),
            TextColumn::make('valid_until')->label('Hasta')->date()->placeholder('Sin fin'),
            TextColumn::make('items_count')->counts('items')->label('Tareas'),
            TextColumn::make('work_orders_count')->counts('workOrders')->label('Órdenes')->badge(),
            TextColumn::make('status')->label('Estado')->badge(),
        ])->recordUrl(fn (CommunityPlan $record): string => CommunityPlanResource::getUrl('view', ['record' => $record]))->recordActions([
            GeneratePlanWorkOrdersAction::make(),
            Action::make('orders')->label('Abrir órdenes')->icon('heroicon-o-clipboard-document-list')->url(fn (CommunityPlan $record): string => CommunityPlanResource::getUrl('orders', ['record' => $record])),
        ]);
    }
}
