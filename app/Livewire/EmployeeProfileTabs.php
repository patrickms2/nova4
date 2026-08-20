<?php

namespace App\Livewire;

use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\ShiftSwapRequest;
use App\Models\TaxistaDocument;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema as DbSchema;
use Livewire\Attributes\On;
use Livewire\Component;

class EmployeeProfileTabs extends Component
{
    public string $activeTab = 'turnos';

    public int $turnosPage = 1;

    public int $turnosPerPage = 5;

    public bool $embedded = false;

    public string $context = 'portal';

    public function mount(): void
    {
        $tab = (string)request()->query('employee_tab', '');

        if ($tab === 'vacaciones' || $tab === 'permisos') {
            $tab = 'permisos';
        }

        if (in_array($tab, ['profile', 'turnos'], true)) {
            $this->activeTab = $tab;
        } else {
            $this->activeTab = 'turnos';
        }
    }

    public function switchTab(string $tab): void
    {
        if ($tab === 'vacaciones' || $tab === 'permisos') {
            $tab = 'turnos';
        }

        if (in_array($tab, ['profile', 'turnos', 'documentos'], true)) {
            $this->activeTab = $tab;
        }
    }

    /**
     * @param  array<string, mixed>  $notification
     */
    #[On('portal-taxista-refresh')]
    public function handlePortalTaxistaRefresh(array $notification = []): void
    {
        $payload = is_array($notification['notification'] ?? null)
            ? $notification['notification']
            : $notification;

        $entity = strtolower(trim((string) ($payload['taxista_entity'] ?? '')));
        $action = strtolower(trim((string) ($payload['taxista_action'] ?? '')));

        if (! in_array($entity, ['timeoff', 'shift_swap'], true)) {
            return;
        }

        if (! in_array($action, ['', 'status_changed', 'answered', 'updated', 'created'], true)) {
            return;
        }
    }

    public function nextTurnosPage(): void
    {
        if (($this->shiftSummary()['has_more_upcoming'] ?? false) !== true) {
            return;
        }

        $this->turnosPage++;
    }

    public function previousTurnosPage(): void
    {
        if ($this->turnosPage <= 1) {
            return;
        }

        $this->turnosPage--;
    }

    /**
     * @return array{name:string,email:string,nif:string,phone:string,department:string,role_label:string,employment_started_at:string}
     */
    public function profileData(): array
    {
        $user = $this->resolveUser();

        if (!$user) {
            return [
                'name' => 'Sin usuario',
                'email' => '-',
                'nif' => '-',
                'phone' => '-',
                'department' => '-',
                'role_label' => 'Empleado',
                'employment_started_at' => '-',
            ];
        }

        // Load the same relationships as EmployeesTable
        $user->loadMissing(['bookingDepartment']);

        return [
            'name' => (string)($user->name ?? 'Sin nombre'),
            'email' => (string)($user->email ?? '-'),
            'nif' => (string)($user->nif ?? '-'),
            'phone' => (string)($user->phone ?? '-'),
            'department' => (string)($user->bookingDepartment?->name ?? '-'),
            'role_label' => 'Empleado',
            'employment_started_at' => $user->employment_started_at?->format('d/m/Y') ?? '-',
        ];
    }

    /**
     * @return array{month_label:string,total:int,m:int,p:int,n:int,l:int,v:int,b:int,next_shift:string,schedule_label:string,upcoming:array<int, array{date:string,shift:string,status:string}>,upcoming_page:int,has_more_upcoming:bool}
     */
    public function shiftSummary(): array
    {
        $user = $this->resolveUser();

        if (!$user) {
            return [
                'month_label' => now()->translatedFormat('F Y'),
                'total' => 0,
                'm' => 0,
                'p' => 0,
                'n' => 0,
                'next_shift' => 'Sin turnos próximos',
                'schedule_label' => 'Horario rotativo',
                'upcoming' => [],
                'upcoming_page' => 1,
                'has_more_upcoming' => false,
            ];
        }

        if (!DbSchema::hasTable('employee_shifts')) {
            return [
                'month_label' => now()->translatedFormat('F Y'),
                'total' => 0,
                'm' => 0,
                'p' => 0,
                'n' => 0,
                'next_shift' => 'Sin turnos próximos',
                'schedule_label' => 'Horario rotativo',
                'upcoming' => [],
                'upcoming_page' => 1,
                'has_more_upcoming' => false,
            ];
        }

        $employeeId = (int)$user->getKey();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $monthShifts = EmployeeShift::query()
            ->where('employee_id', $employeeId)  // Fixed: use employee_id
            ->whereDate('date', '>=', $monthStart)
            ->whereDate('date', '<=', $monthEnd)
            ->get(['date', 'shift_code', 'status']);

        $nextShift = EmployeeShift::query()
            ->with('centralTurno:id,name')
            ->where('employee_id', $employeeId)  // Fixed: use employee_id
            ->whereDate('date', '>=', today()->toDateString())
            ->orderBy('date')
            ->first(['date', 'shift_code', 'central_turno_id', 'status']);

        $upcomingCollection = EmployeeShift::query()
            ->with('centralTurno:id,name')
            ->where('employee_id', $employeeId)  // Fixed: use employee_id
            ->whereDate('date', '>=', today()->toDateString())
            ->orderBy('date')
            ->offset(($this->turnosPage - 1) * $this->turnosPerPage)
            ->limit($this->turnosPerPage + 1)
            ->get(['date', 'shift_code', 'central_turno_id', 'status'])
            ->map(function (EmployeeShift $shift): array {
                return [
                    'date' => $shift->date->format('d/m/Y'),
                    'shift' => (string)($shift->centralTurno?->name ?? $shift->shift_code),
                    'status' => (string)($shift->status ?? 'planned'),
                ];
            });

        $hasMoreUpcoming = $upcomingCollection->count() > $this->turnosPerPage;
        $upcoming = $upcomingCollection->take($this->turnosPerPage)->values()->all();

        return [
            'month_label' => now()->translatedFormat('F Y'),
            'total' => $monthShifts->count(),
            'm' => $monthShifts->where('shift_code', EmployeeShift::SHIFT_MANANA)->count(),
            'p' => $monthShifts->where('shift_code', EmployeeShift::SHIFT_PARTIDO)->count(),
            'n' => $monthShifts->where('shift_code', EmployeeShift::SHIFT_NOCHE)->count(),
            'l' => $monthShifts->where('shift_code', EmployeeShift::SHIFT_LIBRE)->count(),
            'v' => $monthShifts->where('shift_code', EmployeeShift::SHIFT_VACACIONES)->count(),
            'b' => $monthShifts->where('shift_code', EmployeeShift::SHIFT_BAJA)->count(),
            'next_shift' => $nextShift
                ? $nextShift->date->format('d/m/Y') . ' · ' . (string)($nextShift->centralTurno?->name ?? $nextShift->shift_code)
                : 'Sin turnos próximos',
            'schedule_label' => $this->resolveScheduleLabel($employeeId),
            'upcoming' => $upcoming,
            'upcoming_page' => $this->turnosPage,
            'has_more_upcoming' => $hasMoreUpcoming,
        ];
    }

    /**
     * @return array{requests:int,pending:int,approved:int,items:array<int, array{range:string,status:string,type:string,type_label:string,notes:?string}>}
     */
    public function vacationSummary(): array
    {
        $user = $this->resolveUser();

        if (!$user) {
            return [
                'requests' => 0,
                'pending' => 0,
                'approved' => 0,
                'items' => [],
            ];
        }

        $user->loadMissing(['employeeTimeOff']);

        $timeOffItems = $user->employeeTimeOff()
            ->orderByDesc('start_date')
            ->get(['start_date', 'end_date', 'status', 'type', 'notes']);

        $swapItems = DbSchema::hasTable('shift_swap_requests')
            ? ShiftSwapRequest::query()
                ->where(function ($query) use ($user): void {
                    $query
                        ->where('requester_user_id', $user->getKey())
                        ->orWhere('target_user_id', $user->getKey());
                })
                ->orderByDesc('swap_date')
                ->get(['swap_date', 'status', 'type', 'requester_notes', 'review_notes'])
            : collect();

        $items = collect()
            ->merge($timeOffItems->map(fn (EmployeeTimeOff $item): array => [
                'sort_date' => Carbon::parse((string) $item->start_date),
                'status_priority' => in_array((string) $item->status, ['pending', 'target_accepted'], true) ? 0 : 1,
                'range' => Carbon::parse((string) $item->start_date)->format('d/m/Y') . ' - ' . Carbon::parse((string) $item->end_date)->format('d/m/Y'),
                'status' => (string) $item->status,
                'type' => (string) $item->type,
                'type_label' => match ((string) $item->type) {
                    EmployeeTimeOff::TYPE_VACACIONES => 'Vacaciones',
                    EmployeeTimeOff::TYPE_BAJA => 'Baja médica',
                    EmployeeTimeOff::TYPE_PERSONAL => 'Día personal',
                    default => 'Permiso',
                },
                'notes' => $item->notes,
            ]))
            ->merge($swapItems->map(fn (ShiftSwapRequest $item): array => [
                'sort_date' => Carbon::parse((string) $item->swap_date),
                'status_priority' => in_array((string) $item->status, ['pending', 'target_accepted'], true) ? 0 : 1,
                'range' => Carbon::parse((string) $item->swap_date)->format('d/m/Y'),
                'status' => (string) $item->status,
                'type' => (string) $item->type,
                'type_label' => match ((string) $item->type) {
                    ShiftSwapRequest::TYPE_COVER => 'Cobertura',
                    ShiftSwapRequest::TYPE_DAYOFF => 'Día libre',
                    default => 'Intercambio',
                },
                'notes' => $item->requester_notes ?: $item->review_notes,
            ]))
            ->sort(function (array $left, array $right): int {
                if (($left['status_priority'] ?? 1) !== ($right['status_priority'] ?? 1)) {
                    return ($left['status_priority'] ?? 1) <=> ($right['status_priority'] ?? 1);
                }

                /** @var Carbon $leftDate */
                $leftDate = $left['sort_date'];
                /** @var Carbon $rightDate */
                $rightDate = $right['sort_date'];

                return $rightDate->timestamp <=> $leftDate->timestamp;
            })
            ->values();

        return [
            'requests' => $items->count(),
            'pending' => $items->filter(fn (array $item): bool => in_array($item['status'], ['pending', 'target_accepted'], true))->count(),
            'approved' => $items->where('status', 'approved')->count(),
            'items' => $items
                ->take(10)
                ->map(function (array $item): array {
                    unset($item['sort_date']);
                    unset($item['status_priority']);

                    return $item;
                })
                ->all(),
        ];
    }

    /**
     * @return array<int, array{title:string,date:string,type:string}>
     */
    public function recentDocuments(): array
    {
        $user = $this->resolveUser();

        if (!$user || !DbSchema::hasTable('taxista_documents')) {
            return [];
        }

        return TaxistaDocument::query()
            ->where('taxista_user_id', (int)$user->getKey())
            ->orderByDesc('uploaded_at')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get(['title', 'document_type', 'uploaded_at', 'created_at'])
            ->map(fn(TaxistaDocument $document): array => [
                'title' => (string)($document->title ?? 'Documento'),
                'date' => ($document->uploaded_at ?? $document->created_at)?->format('d/m/Y') ?? '-',
                'type' => strtoupper((string)($document->document_type ?? 'otros')),
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.employee-profile-tabs', [
            'profile' => $this->profileData(),
            'shifts' => $this->shiftSummary(),
            'vacations' => $this->vacationSummary(),
            'documents' => $this->recentDocuments(),
        ]);
    }

    private function resolveUser(): ?User
    {
        // Try multiple guards to find the authenticated user
        $webUser = auth('web')->user();
        $taxistaUser = auth('taxista')->user();
        $defaultUser = auth()->user();
        
        // Check if we have a taxista user and try to find the corresponding User model
        if ($taxistaUser && method_exists($taxistaUser, 'user')) {
            // Taxista model might have a relationship to User model
            return $taxistaUser->user;
        }
        
        // If taxistaUser is actually a User model (different setup)
        if ($taxistaUser instanceof User) {
            return $taxistaUser;
        }
        
        // Try web guard
        if ($webUser instanceof User) {
            return $webUser;
        }
        
        // Try default guard
        if ($defaultUser instanceof User) {
            return $defaultUser;
        }
        
        // If taxista has user_id, try to find the User model
        if ($taxistaUser && isset($taxistaUser->user_id)) {
            return User::find($taxistaUser->user_id);
        }
        
        // Last resort: if we have an ID from any guard, try to find User
        $userId = null;
        if ($taxistaUser && method_exists($taxistaUser, 'getKey')) {
            $userId = $taxistaUser->getKey();
        } elseif ($webUser && method_exists($webUser, 'getKey')) {
            $userId = $webUser->getKey();
        } elseif ($defaultUser && method_exists($defaultUser, 'getKey')) {
            $userId = $defaultUser->getKey();
        }
        
        if ($userId) {
            return User::find($userId);
        }
        
        return null;
    }

    private function resolveScheduleLabel(int $employeeId): string
    {
        if (!DbSchema::hasTable('employee_shifts')) {
            return 'Horario rotativo';
        }

        $lastShift = EmployeeShift::query()
            ->where('employee_id', $employeeId)  // Fixed: use employee_id
            ->whereDate('date', '>=', now()->subDays(21)->toDateString())
            ->latest('date')
            ->value('shift_code');

        return match ((string)$lastShift) {
            EmployeeShift::SHIFT_MANANA => 'Turno habitual: Mañana (M)',
            EmployeeShift::SHIFT_PARTIDO => 'Turno habitual: Partido (P)',
            EmployeeShift::SHIFT_NOCHE => 'Turno habitual: Noche (N)',
            default => 'Horario rotativo',
        };
    }
}
