<?php

namespace App\Filament\Portal\Pages;

use App\Enums\Icons\PhosphorIcons;
use App\Filament\Portal\Schemas\Appointments\AppointmentForm;
use App\Models\TaxiCentral\Meeting;
use App\Support\Portal\PortalTaxistaContext;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use UnitEnum;

class AppointmentsCalendar extends Page
{
    protected string $view = 'filament.portal.pages.appointments-calendar';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = PhosphorIcons::CalendarDuotone;

    protected static ?string $navigationLabel = 'Citas Calendario';

    protected static string|UnitEnum|null $navigationGroup = 'Mi Portal';

    protected ?string $heading = 'Mis Citas';

    protected ?string $subheading = 'Vista calendario mensual.';

    public string $month;

    public function mount(): void
    {
        $this->month = request()->query('month', now()->format('Y-m'));
    }

    public function getCalendarPayload(): array
    {
        $monthStart = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $portalUserId = PortalTaxistaContext::meetingCreatorUserId();

        $meetings = Meeting::query()
            ->with(['tipo', 'department'])
            ->where('created_by_user_id', $portalUserId ?: 0)
            ->whereBetween('scheduled_start_at', [$gridStart, $gridEnd])
            ->orderBy('scheduled_start_at')
            ->get()
            ->groupBy(fn(Meeting $meeting): string => $meeting->scheduled_start_at?->format('Y-m-d') ?? '');

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->format('Y-m-d');

                $week[] = [
                    'date' => $cursor->copy(),
                    'inMonth' => $cursor->month === $monthStart->month,
                    'items' => $meetings->get($key, collect()),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return [
            'monthStart' => $monthStart,
            'prevMonth' => $monthStart->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $monthStart->copy()->addMonth()->format('Y-m'),
            'weeks' => collect($weeks),
        ];
    }

    public function monthLabel(): string
    {
        return Carbon::createFromFormat('Y-m', $this->month)->translatedFormat('F Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table')
                ->label('Tabla')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->outlined()
                ->url(Appointments::getUrl()),
            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->outlined()
                ->url(AppointmentsKanban::getUrl()),
            CreateAction::make('add_appointment')
                ->label('Añadir')
                ->icon('heroicon-o-plus')
                ->successNotificationTitle('Cita creada')
                ->model(Meeting::class)
                ->schema(fn(Schema $schema) => AppointmentForm::configure($schema))
                ->fillForm(function (): array {
                    return [
                        'created_by_user_id' => PortalTaxistaContext::meetingCreatorUserId(),
                        'status' => 'pendiente',
                    ];
                })
                ->mutateDataUsing(function (array $data): array {
                    $data['created_by_user_id'] = PortalTaxistaContext::meetingCreatorUserId();
                    $data['status'] = $data['status'] ?? 'pendiente';

                    return $data;
                }),
        ];
    }
}
