<?php

namespace App\Filament\App\Community\Resources\Incidents\Pages;

use App\Filament\App\Community\Resources\Incidents\IncidentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Actions\Community\ResolveIncident;
use App\Models\Incident;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Archilex\AdvancedTables\AdvancedTables;
use Illuminate\Support\Facades\Cache;

class ListIncidents extends ListRecords
{
    protected static string $resource = IncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->slideOver()];
    }

    protected function getTabCountsCacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';

        return sprintf('incidents:list:tabs:%s', $panelId);
    }
 protected function getTabCounts(): array
    {
        $today = today()->toDateString();

        return Cache::remember(
            $this->getTabCountsCacheKey(),
            now()->addSeconds(20),
            static function () use ($today): array {
                $totals = Incident::query()
                    ->selectRaw('COUNT(*) as all_count')
                    ->selectRaw('SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_count')
                    ->selectRaw('SUM(CASE WHEN status = "in_pogress" THEN 1 ELSE 0 END) as in_pogress_count')
                    ->selectRaw('SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolve_count')
                    ->selectRaw('SUM(CASE WHEN priority = "urgent" THEN 1 ELSE 0 END) as urgent_count')
                    ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as new_today_count', [$today])
                    ->first();

                $all = (int) ($totals->all_count ?? 0);
               
                return [
                    'all' => $all,
                    'open' => (int) ($totals->open_count ?? 0),
                    'in_progress' => (int) ($totals->in_pogress_count ?? 0),
                    'resolve' => (int) ($totals->resolve_count ?? 0),
                    'urgent' => (int) ($totals->urgent_count ?? 0),
                    'new_today' => (int) ($totals->new_today_count ?? 0),
                ];
            },
        );
    }
     public function getTabs(): array
    {
        $counts = $this->getTabCounts();

        return [
            'all' => Tab::make()
                ->label('Todo')
                ->badge($counts['all']),

            'open' => Tab::make()
                ->label('Abiertas')
                ->badge($counts['open'])
                ->badgeColor('gray')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('status', 'open')),

            'in_progress' => Tab::make()
                ->label('En Progreso')
                ->badge($counts['in_progress'])
                ->badgeColor('info')
                ->icon('heroicon-m-truck')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('status', 'in_progress')),

            'resolve' => Tab::make()
                ->label('Resueltas')
                ->badge($counts['resolve'])
                ->badgeColor('success')
                ->icon('heroicon-m-calendar-days')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('status', 'resolved')),

            'urgent' => Tab::make()
                ->label('Urgentes')
                ->badge($counts['urgent'])
                ->badgeColor('gray')
                ->icon('heroicon-m-calendar')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('priority', 'urgent')),

            'new_today' => Tab::make()
                ->label('Nuevos hoy')
                ->badge($counts['new_today'])
                ->badgeColor('warning')
                ->icon('heroicon-m-plus-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereDate('created_at', today())),
        ];
    }
}
