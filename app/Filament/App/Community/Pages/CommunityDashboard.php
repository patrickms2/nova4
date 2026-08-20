<?php

namespace App\Filament\App\Community\Pages;

use App\Filament\App\Community\Resources\Communities\CommunityResource;
use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use App\Filament\App\Community\Resources\CommunityTickets\CommunityTicketResource;
use App\Filament\App\Community\Resources\Employees\EmployeeResource;
use App\Filament\App\Community\Resources\Incidents\IncidentResource;
use App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource;
use App\Models\Community;
use App\Models\CommunityAppointment;
use App\Models\CommunityPlan;
use App\Models\CommunityTicket;
use App\Models\Employee;
use App\Models\Incident;
use App\Models\WorkOrder;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use App\Filament\App\Resources\Announcements\Widgets\AnnouncementsWidget;

class CommunityDashboard extends Page
{
    protected string $view = 'filament.app.community-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'NOVA Community';

    public static function getNavigationSort(): ?int
    {
        return 0;
    }
    protected function getHeaderWidgets(): array
    {
        return [AnnouncementsWidget::class];
    }
    protected function getViewData(): array
    {
        $today = today();

        $kpis = [
            'todayOrders' => WorkOrder::whereDate('work_date', $today)->count(),
            'pendingOrders' => WorkOrder::whereIn('status', ['pending', 'in_progress'])->count(),
            'openIncidents' => Incident::whereNotIn('status', ['resolved', 'closed'])->count(),
            'urgentIncidents' => Incident::where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count(),
            'openTickets' => CommunityTicket::whereNotIn('status', ['resolved', 'closed'])->count(),
            'inProgressTickets' => CommunityTicket::where('status', 'in_progress')->count(),
            'activeEmployees' => Employee::where('active', true)->count(),
            'activeCommunities' => Community::where('status', 'active')->count(),
            'activePlans' => CommunityPlan::where('status', 'active')->count(),
            'upcomingCitas' => CommunityAppointment::whereIn('status', ['scheduled', 'confirmed'])
                ->where('starts_at', '>=', now())->count(),
        ];

        $latestOrders = WorkOrder::with(['community', 'starter'])
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed')])
            ->whereDate('work_date', $today)
            ->orderBy('status')
            ->limit(6)
            ->get();

        $latestIncidents = Incident::with(['community'])
            ->whereNotIn('status', ['resolved', 'closed'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->latest()
            ->limit(6)
            ->get();

        $latestTickets = CommunityTicket::with(['community', 'person'])
            ->whereNotIn('status', ['resolved', 'closed'])
            ->latest()
            ->limit(6)
            ->get();

        $communities = Community::withCount([
            'workOrders as pending_orders_count' => fn ($q) => $q->whereIn('status', ['pending', 'in_progress']),
            'incidents as open_incidents_count' => fn ($q) => $q->whereNotIn('status', ['resolved', 'closed']),
            'plans as active_plans_count' => fn ($q) => $q->where('status', 'active'),
        ])
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $latestPlans = CommunityPlan::with(['community'])
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $today))
            ->orderBy('valid_from')
            ->limit(6)
            ->get();

        $urls = [
            'workOrders' => WorkOrderResource::getUrl('index'),
            'incidents' => IncidentResource::getUrl('index'),
            'tickets' => CommunityTicketResource::getUrl('index'),
            'employees' => EmployeeResource::getUrl('index'),
            'communities' => CommunityResource::getUrl('index'),
            'plans' => CommunityPlanResource::getUrl('index'),
        ];

        return compact('kpis', 'latestOrders', 'latestIncidents', 'latestTickets', 'communities', 'latestPlans', 'urls');
    }
}
