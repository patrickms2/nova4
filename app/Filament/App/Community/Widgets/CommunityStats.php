<?php

namespace App\Filament\App\Community\Widgets;

use App\Models\Community;
use App\Models\CommunityAppointment;
use App\Models\CommunityOwnerDocument;
use App\Models\CommunityTicket;
use App\Models\Employee;
use App\Models\Incident;
use App\Models\Person;
use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommunityStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Comunidades', Community::where('status', 'active')->count())->icon('heroicon-o-building-office-2'),
            Stat::make('Empleados', Employee::where('active', true)->count())->icon('heroicon-o-users'),
            Stat::make('Servicios hoy', WorkOrder::whereDate('work_date', today())->count())->icon('heroicon-o-calendar-days'),
            Stat::make('Incidencias abiertas', Incident::whereNotIn('status', ['resolved', 'closed'])->count())->color('danger')->icon('heroicon-o-exclamation-triangle'),
            Stat::make('Órdenes pendientes', WorkOrder::whereIn('status', ['pending', 'in_progress'])->count())->color('warning')->icon('heroicon-o-clipboard-document-list'),
            Stat::make('Propietarios', Person::whereHas('communities', fn ($query) => $query->where('community_person.role', 'owner'))->count())->icon('heroicon-o-user-group'),
            Stat::make('Tickets propietarios', CommunityTicket::whereNotIn('status', ['resolved', 'closed'])->count())->color('warning')->icon('heroicon-o-ticket'),
            Stat::make('Citas pendientes', CommunityAppointment::whereIn('status', ['scheduled', 'confirmed'])->where('starts_at', '>=', now())->count())->color('warning')->icon('heroicon-o-calendar-days'),
            Stat::make('Documentos por revisar', CommunityOwnerDocument::where(fn ($query) => $query->where('status', 'expired')->orWhere('expires_at', '<=', now()->addDays(30)))->count())->color('danger')->icon('heroicon-o-document-magnifying-glass'),
        ];
    }
}
