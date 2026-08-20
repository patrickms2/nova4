<?php

namespace App\Livewire;

use App\Models\Community;
use App\Models\CommunityPlan;
use App\Models\Incident;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ComunigestDashboard extends Component
{
    public ?string $date = null;

    public ?int $selectedCommunityId = null;

    public string $viewMode = 'list';

    public bool $showCommunity = false;

    public string $calendarDate = '';

    public bool $showIncidents = false;

    public bool $showTaskModal = false;

    public ?WorkOrder $selectedOrder = null;

    public string $newTaskTitle = '';

    public string $newTaskPriority = 'normal';

    public function mount(): void
    {
        $this->calendarDate = now()->format('Y-m-d');
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function setDate(?string $date): void
    {
        $this->date = $date;
        $this->calendarDate = $date ?? now()->format('Y-m-d');
    }

    public function previousMonth(): void
    {
        $this->calendarDate = Carbon::parse($this->calendarDate)->subMonth()->format('Y-m-d');
    }

    public function nextMonth(): void
    {
        $this->calendarDate = Carbon::parse($this->calendarDate)->addMonth()->format('Y-m-d');
    }

    public function selectDay(int $day): void
    {
        $this->date = Carbon::parse($this->calendarDate)->setDay($day)->format('Y-m-d');
        $this->viewMode = 'list';
    }

    public function openIncidents(int $id): void
    {
        $this->selectedOrder = WorkOrder::with(['incidents.photos', 'community'])->findOrFail($id);
        $this->showIncidents = true;
    }

    public function closeIncidents(): void
    {
        $this->showIncidents = false;
        $this->selectedOrder = null;
    }

    public function openTaskModal(int $id): void
    {
        $this->selectedOrder = WorkOrder::with(['tasks', 'community'])->findOrFail($id);
        $this->newTaskTitle = '';
        $this->newTaskPriority = 'normal';
        $this->showTaskModal = true;
    }

    public function closeTaskModal(): void
    {
        $this->showTaskModal = false;
        $this->newTaskTitle = '';
        $this->newTaskPriority = 'normal';
        $this->selectedOrder = null;
    }

    public function saveTask(): void
    {
        if (! $this->selectedOrder) {
            return;
        }

        $this->validate([
            'newTaskTitle' => 'required|string|max:255',
            'newTaskPriority' => 'required|in:low,normal,high,urgent',
        ]);

        $sort = WorkOrderTask::where('work_order_id', $this->selectedOrder->id)->max('sort') + 1;

        WorkOrderTask::create([
            'work_order_id' => $this->selectedOrder->id,
            'source_type' => 'EXTRA',
            'title' => $this->newTaskTitle,
            'priority' => $this->newTaskPriority,
            'status' => 'pending',
            'sort' => $sort,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->closeTaskModal();
    }

    public function render()
    {
        $communities = Community::orderBy('name')->pluck('name', 'id');
        $communitiesCount = Community::count();
        $plansCount = CommunityPlan::where('status', 'active')
            ->where('valid_until', '>=', now()->format('Y-m-d'))
            ->count();

        $today = now()->format('Y-m-d');

        $employeesCount = User::count();
        $servicesToday = WorkOrder::where('work_date', $today)->count();
        $openIncidents = Incident::where('status', 'open')->count();

        $todayServices = WorkOrder::with(['community', 'starter'])
            ->where('work_date', $today)
            ->orderBy('id')
            ->get();

        $pendingMissions = WorkOrderTask::with(['workOrder.community'])
            ->where('status', 'pending')
            ->whereHas('workOrder', fn ($q) => $q->where('work_date', '>=', $today))
            ->get()
            ->sortBy(fn ($t) => ['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3][$t->priority] ?? 4)
            ->take(6)
            ->values();

        $todayOrders = WorkOrder::where('work_date', $today)->get();
        $totalToday = $todayOrders->count();

        $counts = [
            'todo' => $todayOrders->where('status', 'finished')->count(),
            'progreso' => $todayOrders->where('status', 'in_progress')->count(),
            'sinIncidir' => $todayOrders->where('status', 'pending')->count(),
            'resto' => $todayOrders->whereNotIn('status', ['finished', 'in_progress', 'pending'])->count(),
        ];

        $percentages = array_map(fn ($c) => $totalToday > 0 ? round(($c / $totalToday) * 100) : 0, $counts);

        $stops = [
            'todo' => $percentages['todo'],
            'progreso' => $percentages['todo'] + $percentages['progreso'],
            'sinIncidir' => $percentages['todo'] + $percentages['progreso'] + $percentages['sinIncidir'],
        ];

        $communityStatus = array_merge($percentages, ['stops' => $stops]);

        $ordersQuery = WorkOrder::with([
            'community',
            'tasks' => function ($query) {
                $query->orderBy('sort')->with('comments');
            },
            'incidents',
        ])
            ->when($this->selectedCommunityId, fn ($q) => $q->where('community_id', $this->selectedCommunityId))
            ->orderBy('work_date')
            ->orderBy('id');

        if ($this->date) {
            $orders = (clone $ordersQuery)
                ->where('work_date', $this->date)
                ->get();
        } else {
            $orders = (clone $ordersQuery)
                ->where('work_date', '>=', now()->format('Y-m-d'))
                ->limit(50)
                ->get();
        }

        $stats = [
            'orders' => $orders->count(),
            'completed' => $orders->sum(fn ($o) => $o->tasks->where('status', 'completed')->count()),
            'openIncidents' => $orders->sum(fn ($o) => $o->incidents->where('status', 'open')->count()),
            'communities' => $communitiesCount,
            'plans' => $plansCount,
        ];

        $upcomingPlans = CommunityPlan::with(['community', 'items'])
            ->where('status', 'active')
            ->where('valid_until', '>=', now()->format('Y-m-d'))
            ->when($this->selectedCommunityId, fn ($q) => $q->where('community_id', $this->selectedCommunityId))
            ->orderBy('valid_from')
            ->limit(10)
            ->get();

        $calendarOrders = WorkOrder::with('community')
            ->whereBetween('work_date', [
                Carbon::parse($this->calendarDate)->startOfMonth()->format('Y-m-d'),
                Carbon::parse($this->calendarDate)->endOfMonth()->format('Y-m-d'),
            ])
            ->when($this->selectedCommunityId, fn ($q) => $q->where('community_id', $this->selectedCommunityId))
            ->orderBy('work_date')
            ->get()
            ->groupBy('work_date->format("Y-m-d")');

        $calendar = $this->buildCalendar(Carbon::parse($this->calendarDate), $calendarOrders);

        return view('livewire.comunigest-dashboard', [
            'communities' => $communities,
            'stats' => $stats,
            'orders' => $orders,
            'upcomingPlans' => $upcomingPlans,
            'calendar' => $calendar,
            'calendarMonth' => Carbon::parse($this->calendarDate),
            'employeesCount' => $employeesCount,
            'servicesToday' => $servicesToday,
            'openIncidentsCount' => $openIncidents,
            'todayServices' => $todayServices,
            'pendingMissions' => $pendingMissions,
            'communityStatus' => $communityStatus,
        ])->layout('layouts.front');
    }

    /**
     * @param  Collection<int, WorkOrder>  $orders
     */
    protected function buildCalendar(Carbon $date, Collection $orders): array
    {
        $start = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];
        $current = $start->copy();

        while ($current <= $end) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dayKey = $current->format('Y-m-d');
                $week[] = [
                    'day' => $current->day,
                    'key' => $dayKey,
                    'current' => $current->month === $date->month,
                    'orders' => $orders->get($dayKey, collect()),
                ];
                $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }
}
