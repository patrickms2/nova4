<?php

namespace App\Filament\App\Community\Resources\WorkOrders\Pages;

use App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Archilex\AdvancedTables\AdvancedTables;
use Illuminate\Support\Facades\Cache;
use App\Actions\Community\TransitionWorkOrder;
use App\Models\WorkOrder;
class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;


   protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
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
                /** @var object{all_count:int|string|null,active_count:int|string|null,inactive_count:int|string|null,new_today_count:int|string|null}|null $totals */
                $totals = WorkOrder::query()
                    ->selectRaw('COUNT(*) as all_count')
                    ->selectRaw('SUM(CASE WHEN DATE(work_date) = ? THEN 1 ELSE 0 END) as new_today_count', [$today])
                    ->selectRaw('SUM(CASE WHEN DATE(work_date) > ? THEN 1 ELSE 0 END) as proximas_count', [$today])
                    ->selectRaw('SUM(CASE WHEN DATE(work_date) < ? THEN 1 ELSE 0 END) as anteriores_count', [$today])
                    ->selectRaw('SUM(CASE WHEN (status = "open" OR status = "pending") THEN 1 ELSE 0 END) as open_count')
                    ->selectRaw('SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_count')
                    ->selectRaw('SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolve_count')
                    ->first();

                $all = (int) ($totals->all_count ?? 0);
               
                return [
                    'all' => $all,
                    'new_today' => (int) ($totals->new_today_count ?? 0),
                    'proximas' => (int) ($totals->proximas_count ?? 0),
                    'anteriores' => (int) ($totals->anteriores_count ?? 0),
                    'open' => (int) ($totals->open_count ?? 0),
                    'in_progress' => (int) ($totals->in_progress_count ?? 0),
                    'resolve' => (int) ($totals->resolve_count ?? 0),
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

            'new_today' => Tab::make()
                ->label('Hoy')
                ->badge($counts['new_today'])
                ->badgeColor('warning')
                ->icon('heroicon-m-plus-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereDate('work_date', today())),

            'proximas' => Tab::make()
                ->label('Próximas')
                ->badge($counts['proximas'])
                ->badgeColor('gray')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereDate('work_date','>=', now()->toDateString())->orderBy('work_date','asc')),

            'pasadas' => Tab::make()
                ->label('Pasadas')
                ->badge($counts['anteriores'])
                ->badgeColor('info')
                ->icon('heroicon-m-truck')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereDate('work_date', '<', now()->toDateString())->orderBy('work_date','desc')),


            'open' => Tab::make()
                ->label('Abiertas')
                ->badge($counts['open'])
                ->badgeColor('gray')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('status', 'pending')),

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


        ];
    }
}
