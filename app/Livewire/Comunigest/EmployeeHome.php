<?php

namespace App\Livewire\Comunigest;

use App\Models\Community;
use App\Models\CommunityPlan;
use App\Models\Incident;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class EmployeeHome extends Component
{
    public ?string $selectedDate = null;

    public ?int $selectedCommunityId = null;

    public string $viewMode = 'list';

    public bool $showCommunity = false;

    public string $calendarDate;

    public function mount(): void
    {
        $this->calendarDate = now()->format('Y-m-d');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function selectDate(?string $date): void
    {
        $this->selectedDate = $date;
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
        $this->selectedDate = Carbon::parse($this->calendarDate)->setDay($day)->format('Y-m-d');
        $this->viewMode = 'list';
    }

    public function render()
    {
        $community = $this->selectedCommunityId
            ? Community::withCount(['workOrders', 'plans'])->find($this->selectedCommunityId)
            : null;

        $communities = Community::orderBy('name')->pluck('name', 'id');

        $communitiesCount = Community::count();
        $plansCount = CommunityPlan::where('status', 'active')->where('valid_until', '>=', now()->format('Y-m-d'))->count();

        $workOrdersQuery = WorkOrder::with(['community', 'tasks'])
            ->when($this->selectedCommunityId, fn ($q) => $q->where('community_id', $this->selectedCommunityId))
            ->orderBy('work_date');

        if ($this->selectedDate) {
            $workOrders = (clone $workOrdersQuery)
                ->where('work_date', $this->selectedDate)
                ->get();
        } else {
            $workOrders = (clone $workOrdersQuery)
                ->where('work_date', '>=', now()->format('Y-m-d'))
                ->limit(5)
                ->get();
        }

        $upcomingPlans = CommunityPlan::with('community')
            ->where('status', 'active')
            ->where('valid_until', '>=', now()->format('Y-m-d'))
            ->when($this->selectedCommunityId, fn ($q) => $q->where('community_id', $this->selectedCommunityId))
            ->orderBy('valid_from')
            ->limit(5)
            ->get();

        $urgentIncidents = Incident::where('status', 'open')
            ->where('priority', 'urgent')
            ->when($this->selectedCommunityId, fn ($q) => $q->where('community_id', $this->selectedCommunityId))
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        $calendarOrders = WorkOrder::with('community')
            ->whereBetween('work_date', [
                Carbon::parse($this->calendarDate)->startOfMonth()->format('Y-m-d'),
                Carbon::parse($this->calendarDate)->endOfMonth()->format('Y-m-d'),
            ])
            ->when($this->selectedCommunityId, fn ($q) => $q->where('community_id', $this->selectedCommunityId))
            ->orderBy('work_date')
            ->limit(5)
            ->get()
            ->groupBy('work_date->format("Y-m-d")');

        $calendar = $this->buildCalendar(Carbon::parse($this->calendarDate), $calendarOrders);

        return view('livewire.comunigest.employee-home', [
            'communities' => $communities,
            'community' => $community,
            'communitiesCount' => $communitiesCount,
            'plansCount' => $plansCount,
            'workOrders' => $workOrders,
            'upcomingPlans' => $upcomingPlans,
            'urgentIncidents' => $urgentIncidents,
            'calendar' => $calendar,
            'calendarDate' => Carbon::parse($this->calendarDate),
        ])->layout('layouts.mobile');
    }

    /**
     * @param Collection<int, WorkOrder> $orders
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
