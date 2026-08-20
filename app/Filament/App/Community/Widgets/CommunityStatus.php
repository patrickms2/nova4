<?php

namespace App\Filament\App\Community\Widgets;

use App\Models\Community;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CommunityStatus extends TableWidget
{
    protected static ?string $heading = 'Estado de comunidades';

    public function table(Table $table): Table
    {
        return $table->query(fn (): Builder => Community::query()->withCount([
            'workOrders as pending_orders_count' => fn ($query) => $query->whereIn('status', ['pending', 'in_progress']),
            'incidents as open_incidents_count' => fn ($query) => $query->whereNotIn('status', ['resolved', 'closed']),
            'plans as active_plans_count' => fn ($query) => $query->where('status', 'active')->where(fn ($query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', today())),
        ])->orderBy('name'))->columns([
            TextColumn::make('name')->label('Comunidad')->searchable(),
            TextColumn::make('pending_orders_count')->label('Órdenes pendientes')->badge(),
            TextColumn::make('open_incidents_count')->label('Incidencias')->badge()->color(fn (int $state): string => $state > 0 ? 'danger' : 'success'),
            TextColumn::make('active_plans_count')->label('Planes activos'),
            TextColumn::make('status')->label('Estado')->badge(),
        ]);
    }
}
