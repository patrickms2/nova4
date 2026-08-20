<?php

namespace App\Livewire;

use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\User;
use App\Services\Hrm\EmployeeTimeOffService;
use App\Services\Hrm\ShiftSwapService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema as DbSchema;
use Livewire\Component;

class EmployeeShiftCalendar extends Component
{
    public int $year;

    public int $month;

    public ?string $selectedDate = null;

    public ?string $selectedShiftCode = null;

    public ?int $selectedShiftId = null;

    public bool $showDayModal = false;

    public string $requestType = '';

    public string $requestNotes = '';

    public bool $showRequestForm = false;

    public ?int $swapTargetUserId = null;

    public ?int $swapTargetShiftId = null;

    public ?string $selectedTimeOffType = null;

    public ?string $selectedTimeOffStatus = null;

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
        $this->closeDayModal();
    }

    public function nextMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
        $this->closeDayModal();
    }

    public function selectDay(string $date): void
    {
        $this->selectedDate = $date;
        $this->showDayModal = true;
        $this->showRequestForm = false;
        $this->requestType = '';
        $this->requestNotes = '';
        $this->swapTargetUserId = null;
        $this->swapTargetShiftId = null;
        $this->selectedTimeOffType = null;
        $this->selectedTimeOffStatus = null;

        $userId = $this->resolveUserId();

        if ($userId && DbSchema::hasTable('employee_shifts')) {
            $shift = EmployeeShift::query()
                ->where('employee_id', $userId)
                ->whereDate('date', $date)
                ->first(['id', 'shift_code']);

            $this->selectedShiftCode = $shift?->shift_code;
            $this->selectedShiftId = $shift?->id;
        }

        if ($userId && DbSchema::hasTable('employee_time_off')) {
            $timeOff = EmployeeTimeOff::query()
                ->where('employee_id', $userId)
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->orderByRaw("FIELD(status, 'approved', 'pending', 'denied')")
                ->first(['type', 'status']);

            $this->selectedTimeOffType = $timeOff?->type;
            $this->selectedTimeOffStatus = $timeOff?->status;

            if ($timeOff?->status === EmployeeTimeOff::STATUS_APPROVED) {
                $overlay = app(EmployeeTimeOffService::class)->buildCalendarOverlayMap($userId, Carbon::parse($date), Carbon::parse($date));
                $this->selectedShiftCode = $overlay[$date]['shift_code'] ?? $this->selectedShiftCode;
            }
        }
    }

    public function closeDayModal(): void
    {
        $this->showDayModal = false;
        $this->selectedDate = null;
        $this->selectedShiftCode = null;
        $this->selectedShiftId = null;
        $this->showRequestForm = false;
        $this->requestType = '';
        $this->requestNotes = '';
        $this->swapTargetUserId = null;
        $this->swapTargetShiftId = null;
        $this->selectedTimeOffType = null;
        $this->selectedTimeOffStatus = null;
    }

    public function startRequest(string $type): void
    {
        if ($this->selectedTimeOffStatus === EmployeeTimeOff::STATUS_APPROVED) {
            return;
        }

        $this->requestType = $type;
        $this->showRequestForm = true;
    }

    public function updatedSwapTargetUserId(): void
    {
        if (! $this->swapTargetUserId || ! $this->selectedDate) {
            $this->swapTargetShiftId = null;

            return;
        }

        $availableShiftIds = array_keys($this->swapTargetShiftOptions());

        $this->swapTargetShiftId = $availableShiftIds !== [] ? (int) $availableShiftIds[0] : null;
    }

    public function submitRequest(): void
    {
        $userId = $this->resolveUserId();

        if (! $userId || ! $this->selectedDate || ! $this->requestType) {
            return;
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return;
        }

        if ($this->requestType === 'dayoff') {
            if (DbSchema::hasTable('employee_time_off')) {
                EmployeeTimeOff::query()->create([
                    'employee_id' => $userId,
                    'user_id' => $userId,
                    'booking_department_id' => $user->booking_department_id,
                    'start_date' => $this->selectedDate,
                    'end_date' => $this->selectedDate,
                    'is_full_day' => true,
                    'type' => EmployeeTimeOff::TYPE_PERSONAL,
                    'status' => EmployeeTimeOff::STATUS_PENDING,
                    'notes' => filled($this->requestNotes) ? $this->requestNotes : null,
                ]);
            }
        }

        if ($this->requestType === 'swap') {
            if (DbSchema::hasTable('shift_swap_requests')) {
                if (! $this->selectedShiftId || ! $this->swapTargetUserId || ! $this->swapTargetShiftId) {
                    $this->dispatch('notify', type: 'danger', message: 'Selecciona un compañero libre y su turno libre para enviar el intercambio.');

                    return;
                }

                try {
                    app(ShiftSwapService::class)->createSwapRequest(
                        requesterUserId: $userId,
                        requesterShiftId: $this->selectedShiftId,
                        targetUserId: $this->swapTargetUserId,
                        targetShiftId: $this->swapTargetShiftId,
                        swapDate: $this->selectedDate,
                        departmentId: $user->booking_department_id,
                        requesterNotes: $this->requestNotes,
                    );
                } catch (\Throwable $exception) {
                    $this->dispatch('notify', type: 'danger', message: $exception->getMessage());

                    return;
                }
            }
        }

        $this->closeDayModal();

        $this->dispatch('notify', type: 'success', message: 'Solicitud enviada correctamente.');
    }

    /**
     * @return array<string, array{shift_code:string, status:string, color:string, label:string}>
     */
    public function calendarDays(): array
    {
        $userId = $this->resolveUserId();

        if (! $userId || ! DbSchema::hasTable('employee_shifts')) {
            return [];
        }

        $start = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth()->toDateString();
        $end = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth()->toDateString();
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        $days = EmployeeShift::query()
            ->where('employee_id', $userId)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->get(['date', 'shift_code', 'status'])
            ->keyBy(fn (EmployeeShift $s): string => $s->date->toDateString())
            ->map(fn (EmployeeShift $s): array => [
                'shift_code' => (string) $s->shift_code,
                'status' => (string) $s->status,
                'color' => $this->shiftColor((string) $s->shift_code),
                'label' => $this->shiftLabel((string) $s->shift_code),
            ])
            ->all();

        return array_replace(
            $days,
            app(EmployeeTimeOffService::class)->buildCalendarOverlayMap($userId, $startDate, $endDate),
        );
    }

    /**
     * @return array{m:int, p:int, n:int, l:int, total:int}
     */
    public function monthSummary(): array
    {
        $days = $this->calendarDays();

        $m = collect($days)->where('shift_code', EmployeeShift::SHIFT_MANANA)->count();
        $p = collect($days)->where('shift_code', EmployeeShift::SHIFT_PARTIDO)->count();
        $n = collect($days)->where('shift_code', EmployeeShift::SHIFT_NOCHE)->count();
        $l = collect($days)->where('shift_code', EmployeeShift::SHIFT_LIBRE)->count();

        return [
            'm' => $m,
            'p' => $p,
            'n' => $n,
            'l' => $l,
            'total' => count($days),
        ];
    }

    public function render()
    {
        $monthDate = Carbon::createFromDate($this->year, $this->month, 1);

        return view('livewire.employee-shift-calendar', [
            'year' => $this->year,
            'month' => $this->month,
            'selectedDate' => $this->selectedDate,
            'selectedShiftCode' => $this->selectedShiftCode,
            'selectedShiftId' => $this->selectedShiftId,
            'showDayModal' => $this->showDayModal,
            'requestType' => $this->requestType,
            'requestNotes' => $this->requestNotes,
            'showRequestForm' => $this->showRequestForm,
            'selectedTimeOffType' => $this->selectedTimeOffType,
            'selectedTimeOffStatus' => $this->selectedTimeOffStatus,
            'swapTargetOptions' => $this->swapTargetOptions(),
            'swapTargetShiftOptions' => $this->swapTargetShiftOptions(),
            'monthLabel' => $monthDate->translatedFormat('F Y'),
            'calendarDays' => $this->calendarDays(),
            'summary' => $this->monthSummary(),
            'daysInMonth' => (int) $monthDate->daysInMonth,
            'startDayOfWeek' => ($monthDate->dayOfWeekIso - 1),
        ])->layout('layouts.portal-calendar', ['title' => 'Mis Turnos']);
    }

    private function shiftColor(string $code): string
    {
        return match ($code) {
            EmployeeShift::SHIFT_MANANA => '#3b82f6',
            EmployeeShift::SHIFT_PARTIDO => '#f59e0b',
            EmployeeShift::SHIFT_NOCHE => '#8b5cf6',
            EmployeeShift::SHIFT_LIBRE => '#22c55e',
            EmployeeShift::SHIFT_VACACIONES => '#06b6d4',
            EmployeeShift::SHIFT_BAJA => '#ef4444',
            EmployeeShift::SHIFT_SALIENTE => '#6b7280',
            default => '#64748b',
        };
    }

    private function shiftLabel(string $code): string
    {
        return match ($code) {
            EmployeeShift::SHIFT_MANANA => 'Mañana',
            EmployeeShift::SHIFT_PARTIDO => 'Partido',
            EmployeeShift::SHIFT_NOCHE => 'Noche',
            EmployeeShift::SHIFT_LIBRE => 'Libre',
            EmployeeShift::SHIFT_VACACIONES => 'Vacaciones',
            EmployeeShift::SHIFT_BAJA => 'Baja',
            EmployeeShift::SHIFT_SALIENTE => 'Saliente',
            default => $code,
        };
    }

    private function resolveUserId(): ?int
    {
        $authUser = auth('web')->user() ?? auth('taxista')->user();

        return $authUser?->getAuthIdentifier();
    }

    /**
     * @return array<int, string>
     */
    public function swapTargetOptions(): array
    {
        $userId = $this->resolveUserId();

        if (! $userId || ! $this->selectedDate) {
            return [];
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return [];
        }

        return app(ShiftSwapService::class)->getAvailableTargetsForDate(
            requesterUserId: $userId,
            swapDate: $this->selectedDate,
            departmentId: $user->booking_department_id,
        );
    }

    /**
     * @return array<int, string>
     */
    public function swapTargetShiftOptions(): array
    {
        if (! $this->swapTargetUserId || ! $this->selectedDate) {
            return [];
        }

        return app(ShiftSwapService::class)->getAvailableTargetShifts(
            targetUserId: $this->swapTargetUserId,
            swapDate: $this->selectedDate,
        );
    }
}
