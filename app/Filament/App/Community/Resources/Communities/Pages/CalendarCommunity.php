<?php

namespace App\Filament\App\Community\Resources\Communities\Pages;

use App\Filament\App\Community\Resources\Communities\CommunityResource;
use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class CalendarCommunity extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CommunityResource::class;

    protected string $view = 'filament.app.community.communities.calendar';

    protected static ?string $navigationLabel = 'Calendario Órdenes';
    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';
    protected static ?string $navigationParentGroup = 'Nova Community';
    
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public string $month;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->month = request()->query('month', now()->format('Y-m'));
        static::authorizeResourceAccess();
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Calendario · '.$this->getRecord()->name;
    }

    /** @return array{label: string, previous: string, next: string, weeks: array<int, array<int, array{date: Carbon, inMonth: bool, orders: mixed}>>} */
    public function calendar(): array
    {
        $month = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $start = $month->copy()->startOfWeek();
        $end = $month->copy()->endOfMonth()->endOfWeek();
        $orders = WorkOrder::query()
            ->select(['id', 'community_id', 'community_plan_id', 'code', 'work_date', 'status'])
            ->with('plan:id,name')
            ->withCount(['tasks', 'tasks as pending_tasks_count' => fn ($query) => $query->where('status', 'pending')])
            ->whereBelongsTo($this->getRecord(), 'community')
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->groupBy(fn (WorkOrder $order): string => $order->work_date->format('Y-m-d'));

        $weeks = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $week = [];

            for ($day = 0; $day < 7; $day++) {
                $key = $cursor->format('Y-m-d');
                $week[] = ['date' => $cursor->copy(), 'inMonth' => $cursor->month === $month->month, 'orders' => $orders->get($key, collect())];
                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return ['label' => $month->translatedFormat('F Y'), 'previous' => $month->copy()->subMonth()->format('Y-m'), 'next' => $month->copy()->addMonth()->format('Y-m'), 'weeks' => $weeks];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('community')->label('Resumen')->icon('heroicon-o-building-office-2')->url(fn (): string => CommunityResource::getUrl('view', ['record' => $this->getRecord()])),
            Action::make('plans')->label('Planes')->icon('heroicon-o-clipboard-document-check')->url(CommunityPlanResource::getUrl('index', ['tableFilters' => ['community' => ['value' => $this->getRecord()->getKey()]]])),
        ];
    }
}
