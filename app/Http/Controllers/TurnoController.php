<?php

namespace App\Http\Controllers;

use App\Models\CentralTurno;
use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TurnoController extends Controller
{
    private function formatTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        if (preg_match('/^(\d{2}:\d{2})/', $string, $matches) === 1) {
            return $matches[1];
        }

        try {
            return Carbon::parse($string)->format('H:i');
        } catch (\Throwable) {
            return $string;
        }
    }

    public function calendar(Request $request): View
    {
        $startDate = Carbon::parse((string) $request->input('start_date', now()->startOfWeek()->toDateString()))->toDateString();
        $endDate = Carbon::parse((string) $request->input('end_date', now()->endOfWeek()->toDateString()))->toDateString();
        $employeeId = $request->integer('employee_id');

        return view('turnos.calendar', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'employeeId' => $employeeId ?: null,
        ]);
    }

    public function index(): JsonResponse
    {
        $request = request();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $bookingDepartmentId = $request->input('booking_department_id');
        $employeeId = $request->input('employee_id');

        $employees = User::query()
            ->where('status', true)
            ->where(function ($query): void {
                $query->where('role', 'empleado')->orWhere('is_employee', true);
            })
            ->when($bookingDepartmentId, fn ($query) => $query->where('booking_department_id', $bookingDepartmentId))
            ->when($employeeId, fn ($query) => $query->whereKey((int) $employeeId))
            ->orderBy('name')
            ->get(['id', 'name', 'booking_department_id']);

        $turnos = EmployeeShift::query()
            ->with(['employee:id,name', 'centralTurno:id,code,name,start_time,end_time'])
            ->when($startDate, fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->when($bookingDepartmentId, fn ($query) => $query->where('booking_department_id', $bookingDepartmentId))
            ->when($employeeId, fn ($query) => $query->where('employee_id', (int) $employeeId))
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get()
            ->map(function (EmployeeShift $shift): array {
                return [
                    'id' => $shift->id,
                    'employee_id' => (int) $shift->employee_id,
                    'booking_department_id' => $shift->booking_department_id ? (int) $shift->booking_department_id : null,
                    'central_turno_id' => $shift->central_turno_id ? (int) $shift->central_turno_id : null,
                    'date' => $shift->date?->toDateString(),
                    'shift_code' => (string) $shift->shift_code,
                    'status' => (string) $shift->status,
                    'notes' => $shift->notes,
                    'employee' => $shift->employee ? [
                        'id' => (int) $shift->employee->id,
                        'name' => (string) $shift->employee->name,
                    ] : null,
                    'central_turno' => $shift->centralTurno ? [
                        'id' => (int) $shift->centralTurno->id,
                        'code' => (string) $shift->centralTurno->code,
                        'name' => (string) $shift->centralTurno->name,
                        'start_time' => $this->formatTime($shift->centralTurno->start_time),
                        'end_time' => $this->formatTime($shift->centralTurno->end_time),
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'employees' => $employees,
            'turnos' => $turnos,
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'booking_department_id' => $bookingDepartmentId,
                'employee_id' => $employeeId ? (int) $employeeId : null,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $employeeId = (int) ($request->input('employee_id') ?? $request->input('empleado_id'));
        $date = (string) ($request->input('date') ?? $request->input('fecha'));
        $type = strtolower(trim((string) ($request->input('type') ?? $request->input('tipo', 'turno'))));
        $turnoInput = (string) ($request->input('shift_code') ?? $request->input('turno', ''));
        $status = (string) $request->input('status', EmployeeShift::STATUS_PLANNED);
        $notes = $request->input('notes');

        $validator = Validator::make([
            'employee_id' => $employeeId,
            'date' => $date,
            'type' => $type,
            'shift_code' => $turnoInput,
            'status' => $status,
            'notes' => $notes,
        ], [
            'employee_id' => 'required|integer|exists:users,id',
            'date' => 'required|date',
            'type' => 'required|in:turno,baja,vacaciones,libre,saliente',
            'shift_code' => 'nullable|string|max:20',
            'status' => 'required|in:planned,confirmed,locked',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $shiftCode = $this->resolveShiftCode($type, $turnoInput);

        $employeeShift = $this->createShiftAssignment($employeeId, $date, $shiftCode, $status, is_string($notes) ? trim($notes) : null);

        return response()->json([
            'message' => 'Registro añadido correctamente',
            'data' => $employeeShift->load(['employee:id,name', 'centralTurno:id,code,name']),
        ], 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $date = (string) ($request->input('date') ?? $request->input('fecha'));
        $type = strtolower(trim((string) ($request->input('type') ?? $request->input('tipo', 'turno'))));
        $turnoInput = (string) ($request->input('shift_code') ?? $request->input('turno', ''));
        $status = (string) $request->input('status', EmployeeShift::STATUS_PLANNED);
        $employeeIds = $request->input('employee_ids') ?? $request->input('empleado_ids', []);

        $validator = Validator::make([
            'date' => $date,
            'type' => $type,
            'shift_code' => $turnoInput,
            'status' => $status,
            'employee_ids' => $employeeIds,
        ], [
            'date' => 'required|date',
            'type' => 'required|in:turno,baja,vacaciones,libre,saliente',
            'shift_code' => 'nullable|string|max:20',
            'status' => 'required|in:planned,confirmed,locked',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $shiftCode = $this->resolveShiftCode($type, $turnoInput);
        $created = [];

        DB::transaction(function () use ($employeeIds, $date, $shiftCode, $status, &$created): void {
            foreach (collect($employeeIds)->unique()->values() as $employeeId) {
                $created[] = $this->createShiftAssignment((int) $employeeId, $date, $shiftCode, $status);
            }
        });

        return response()->json([
            'message' => 'Asignación masiva realizada correctamente',
            'data' => collect($created)->map(fn (EmployeeShift $shift) => $shift->load(['employee:id,name', 'centralTurno:id,code,name'])),
        ], 201);
    }

    private function createShiftAssignment(int $employeeId, string $date, string $shiftCode, string $status, ?string $notes = null): EmployeeShift
    {
        if (EmployeeShift::query()->where('employee_id', $employeeId)->whereDate('date', $date)->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => 'El empleado ya tiene un registro (turno, baja o vacaciones) en esa fecha.',
            ]);
        }

        if ($this->isWorkingShift($shiftCode)) {
            $limit = $this->limitForShift($shiftCode);
            $assigned = $this->assignedCountForDateShift($date, $shiftCode);

            if ($assigned >= $limit) {
                throw ValidationException::withMessages([
                    'shift_code' => "El turno {$shiftCode} ya está completo para {$date}.",
                ]);
            }
        }

        if ($this->isWorkingShift($shiftCode)) {
            $hasApprovedTimeOff = EmployeeTimeOff::query()
                ->where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();

            if ($hasApprovedTimeOff) {
                throw ValidationException::withMessages([
                    'employee_id' => 'El empleado tiene una ausencia aprobada en esa fecha.',
                ]);
            }
        }

        $employee = User::query()->findOrFail($employeeId);

        $employeeShift = EmployeeShift::query()->create([
            'employee_id' => $employeeId,
            'booking_department_id' => $employee->booking_department_id,
            'central_turno_id' => $this->resolveCentralTurnoId($shiftCode),
            'date' => $date,
            'shift_code' => $shiftCode,
            'status' => $status,
            'notes' => $notes !== '' ? $notes : null,
        ]);

        if (in_array($shiftCode, [EmployeeShift::SHIFT_VACACIONES, EmployeeShift::SHIFT_BAJA], true)) {
            EmployeeTimeOff::query()->updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'start_date' => $date,
                    'end_date' => $date,
                ],
                [
                    'type' => $shiftCode === EmployeeShift::SHIFT_VACACIONES ? 'vacaciones' : 'baja',
                    'status' => 'approved',
                ],
            );
        }

        return $employeeShift;
    }

    public function sugerirTurno(Request $request): JsonResponse
    {
        $date = (string) ($request->input('date') ?? $request->input('fecha'));
        $turnoInput = (string) ($request->input('shift_code') ?? $request->input('turno', ''));

        $validator = Validator::make([
            'date' => $date,
            'shift_code' => $turnoInput,
        ], [
            'date' => 'required|date',
            'shift_code' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $shiftCode = $this->resolveShiftCode('turno', $turnoInput);

        if (! $this->isWorkingShift($shiftCode)) {
            return response()->json(['empleado_id' => null, 'mensaje' => 'Solo se sugieren turnos de trabajo (M, P, N).']);
        }

        $limit = $this->limitForShift($shiftCode);
        $assigned = $this->assignedCountForDateShift($date, $shiftCode);

        if ($assigned >= $limit) {
            return response()->json([
                'empleado_id' => null,
                'mensaje' => "El turno {$shiftCode} está completo.",
            ]);
        }

        $dateAsCarbon = Carbon::parse($date);

        $employee = User::query()
            ->where('status', true)
            ->where('is_employee', true)
            ->where(function ($query): void {
                $query->where('role', 'empleado')->orWhereNull('role');
            })
            ->whereDoesntHave('employeeShifts', fn ($query) => $query->whereDate('date', $dateAsCarbon->toDateString()))
            ->whereDoesntHave('employeeTimeOff', function ($query) use ($dateAsCarbon): void {
                $query->where('status', 'approved')
                    ->whereDate('start_date', '<=', $dateAsCarbon->toDateString())
                    ->whereDate('end_date', '>=', $dateAsCarbon->toDateString());
            })
            ->withCount('employeeShifts')
            ->orderBy('employee_shifts_count')
            ->orderBy('id')
            ->first();

        return response()->json(['empleado_id' => $employee?->id]);
    }

    private function resolveShiftCode(string $type, string $turnoInput): string
    {
        if ($type === 'vacaciones') {
            return EmployeeShift::SHIFT_VACACIONES;
        }

        if ($type === 'libre') {
            return EmployeeShift::SHIFT_LIBRE;
        }

        if ($type === 'saliente') {
            return EmployeeShift::SHIFT_SALIENTE;
        }

        if ($type === 'baja') {
            return EmployeeShift::SHIFT_BAJA;
        }

        $value = strtoupper(trim($turnoInput));

        return match ($value) {
            'MANANA', 'MAÑANA', 'M' => EmployeeShift::SHIFT_MANANA,
            'TARDE', 'PARTIDO', 'P', 'T' => EmployeeShift::SHIFT_PARTIDO,
            'NOCHE', 'N' => EmployeeShift::SHIFT_NOCHE,
            default => throw ValidationException::withMessages([
                'shift_code' => 'Turno inválido. Usa M, P o N.',
            ]),
        };
    }

    private function resolveCentralTurnoId(string $shiftCode): ?int
    {
        if (! $this->isWorkingShift($shiftCode)) {
            return null;
        }

        return (int) CentralTurno::query()
            ->whereIn('code', $shiftCode === EmployeeShift::SHIFT_PARTIDO ? [EmployeeShift::SHIFT_PARTIDO, 'T'] : [$shiftCode])
            ->value('id');
    }

    private function isWorkingShift(string $shiftCode): bool
    {
        return in_array($shiftCode, [EmployeeShift::SHIFT_MANANA, EmployeeShift::SHIFT_PARTIDO, EmployeeShift::SHIFT_NOCHE], true);
    }

    private function assignedCountForDateShift(string $date, string $shiftCode): int
    {
        if ($shiftCode === EmployeeShift::SHIFT_PARTIDO) {
            return EmployeeShift::query()
                ->whereDate('date', $date)
                ->whereIn('shift_code', [EmployeeShift::SHIFT_PARTIDO, 'T'])
                ->count();
        }

        return EmployeeShift::query()
            ->whereDate('date', $date)
            ->where('shift_code', $shiftCode)
            ->count();
    }

    private function limitForShift(string $shiftCode): int
    {
        return match ($shiftCode) {
            EmployeeShift::SHIFT_MANANA => 3,
            EmployeeShift::SHIFT_PARTIDO => 3,
            EmployeeShift::SHIFT_NOCHE => 1,
            default => 1,
        };
    }
}
