<?php

namespace App\Filament\App\Community\Resources\CommunityAppointments\Pages;

use App\Filament\App\Community\Resources\CommunityAppointments\CommunityAppointmentResource;
use App\Models\CommunityAppointment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class CalendarCommunityAppointments extends Page
{
    protected static string $resource = CommunityAppointmentResource::class;

    protected string $view = 'filament.app.community.appointments.calendar';

    protected static ?string $title = 'Calendario de citas';

    public string $month;

    public function mount(): void
    {
        $this->month = request()->query('month', now()->format('Y-m'));
    }

    public function calendar(): array
    {
        $month = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $start = $month->copy()->startOfWeek();
        $end = $month->copy()->endOfMonth()->endOfWeek();
        $items = CommunityAppointment::with(['person', 'community'])->whereBetween('starts_at', [$start, $end])->orderBy('starts_at')->get()->groupBy(fn (CommunityAppointment $item): string => $item->starts_at->format('Y-m-d'));
        $weeks = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $week = [];
            for ($day = 0; $day < 7; $day++) {
                $key = $cursor->format('Y-m-d');
                $week[] = ['date' => $cursor->copy(), 'inMonth' => $cursor->month === $month->month, 'items' => $items->get($key, collect())];
                $cursor->addDay();
            } $weeks[] = $week;
        }

        return ['label' => $month->translatedFormat('F Y'), 'previous' => $month->copy()->subMonth()->format('Y-m'), 'next' => $month->copy()->addMonth()->format('Y-m'), 'weeks' => $weeks];
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('table')->label('Tabla')->url(CommunityAppointmentResource::getUrl()), Action::make('kanban')->label('Kanban')->url(CommunityAppointmentResource::getUrl('kanban'))];
    }
}
