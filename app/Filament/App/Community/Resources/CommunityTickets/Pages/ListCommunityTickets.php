<?php

namespace App\Filament\App\Community\Resources\CommunityTickets\Pages;

use App\Filament\App\Community\Resources\CommunityTickets\CommunityTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Archilex\AdvancedTables\AdvancedTables;
use Illuminate\Support\Facades\Cache;
use App\Models\CommunityTicket;
use App\Filament\App\Community\Resources\CommunityTickets\Pages\CreateCommunityTicket;
use App\Filament\App\Community\Resources\CommunityTickets\Pages\EditCommunityTicket;
use App\Filament\App\Community\Resources\CommunityTickets\Pages\ViewCommunityTicket;

class ListCommunityTickets extends ListRecords
{
    protected static string $resource = CommunityTicketResource::class;

    protected function getHeaderActions(): array
    {

        return [
                CreateAction::make('incidencia')->label('Nueva Incidencia')
                                       ->icon('euro')
                        ->url(fn($record) => route('filament.community.resources.community-tickets.create',['type' => 'incidencia']))
                        ->openUrlInNewTab(false),
                CreateAction::make('gasto')->label('Nuevo Gasto')                                   ->icon('euro')
                        ->url(fn($record) => route('filament.community.resources.community-tickets.create',['type' => 'expense']))
                        ->openUrlInNewTab(false),
                CreateAction::make('ticket')->label('NuevoTicket')                                   ->icon('euro')
                        ->url(fn($record) => route('filament.community.resources.community-tickets.create',['type' => 'ticket']))
                        ->openUrlInNewTab(false),
        ];
    }


    protected function getTabCountsCacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';

        return sprintf('ticket:list:tabs:%s', $panelId);
    }
 protected function getTabCounts(): array
    {
        $today = today()->toDateString();

        return Cache::remember(
            $this->getTabCountsCacheKey(),
            now()->addSeconds(20),
            static function () use ($today): array {
                $totals = CommunityTicket::query()
                    ->selectRaw('COUNT(*) as all_count')
                    ->selectRaw('SUM(CASE WHEN type = "incident" THEN 1 ELSE 0 END) as incident_count')
                    ->selectRaw('SUM(CASE WHEN type = "expense" THEN 1 ELSE 0 END) as expense_count')
                    ->selectRaw('SUM(CASE WHEN type = "general" THEN 1 ELSE 0 END) as general_count')
                    ->selectRaw('SUM(CASE WHEN priority = "urgent" THEN 1 ELSE 0 END) as urgent_count')
                    ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as new_today_count', [$today])
                    ->first();

                $all = (int) ($totals->all_count ?? 0);
               
                return [
                    'all' => $all,
                    'incident' => (int) ($totals->incident_count ?? 0),
                    'expense' => (int) ($totals->expense_count ?? 0),
                    'general' => (int) ($totals->general_count ?? 0),
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

            'incident' => Tab::make()
                ->label('Incidencias')
                ->badge($counts['incident'])
                ->badgeColor('gray')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('type', 'incident')),

            'expense' => Tab::make()
                ->label('Gastos')
                ->badge($counts['expense'])
                ->badgeColor('info')
                ->icon('heroicon-m-truck')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('type', 'expense')),

            'general' => Tab::make()
                ->label('Generales')
                ->badge($counts['general'])
                ->badgeColor('success')
                ->icon('heroicon-m-calendar-days')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('type', 'general')),

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
