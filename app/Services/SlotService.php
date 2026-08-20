<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SemanaDia;
use App\Models\BookingDepartment;
use App\Models\BookingDepartmentSchedule;
use App\Models\Taxi\OpeningTime;
use App\Models\TaxistaAppointment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class SlotService
{
    /**
     * Obtener los horarios disponibles (slot_id y hora de inicio) para un departamento en un día específico.
     *
     * @param  int  $departamentoId  ID del departamento.
     * @param  int  $dia  Día de la semana (0 = domingo, 6 = sábado).
     * @param  Carbon  $date  Fecha para verificar disponibilidad.
     * @return Collection Lista de horarios disponibles.
     */
    public static function availableFor(int $departamentoId, int $dia, $date): Collection
    {
        $diaSemana = SemanaDia::tryFrom($dia)->name;
        // Obtener los horarios del departamento para el día especificado.
        $horarios = OpeningTime::where('departamento_id', $departamentoId)
            ->whereJsonContains('days', $diaSemana)
            ->get();
        $availableSlots = collect();
        $inicio = 0;
        $fin = 0;
        $filteredSlots = collect();

        foreach ($horarios as $horario) {

            $horas = [];
            foreach ($horario['horarios'] as $key => $value) {

                $horas[$key] = $key;
                $horas[$key] = [$value['open'], $value['close']];
                $duracion = $horario['duration'];
                if ($duracion == '') {
                    $duracion = 60;
                }

                $inicio = Carbon::parse($horas[$key][0]);
                $fin = Carbon::parse($horas[$key][1]);

                $i = 0;
                while ($inicio->copy()->addMinutes($duracion)->lte($fin) && $i < 8) {

                    $availableSlots->push([
                        'id' => $inicio->format('H:i'), // ID único basado en el timestamp.
                        'time' => $inicio->format('H:i'), // Hora en formato 'H:i'.
                    ]);
                    $inicio->addMinutes($duracion);
                    $i++;

                }
            }
        }

        return $availableSlots;
    }

    /**
     * @return array{options: array<string, string>, disabled: array<string, bool>, duration: int}
     */
    public static function buildSlotsForDepartmentDate(int $bookingDepartmentId, CarbonInterface $date, ?int $ignoreAppointmentId = null): array
    {
        if (! Schema::hasTable('booking_department_schedules')) {
            return [
                'options' => [],
                'disabled' => [],
                'duration' => 30,
            ];
        }

        $schedules = BookingDepartmentSchedule::query()
            ->where('booking_department_id', $bookingDepartmentId)
            ->where('is_active', true)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->orderBy('start_time')
            ->get();

        $duration = (int) BookingDepartment::query()->whereKey($bookingDepartmentId)->value('meeting_duration');
        $duration = $duration > 0 ? $duration : 30;

        $reserved = collect();

        if (Schema::hasTable('taxista_appointments')) {
            $reserved = TaxistaAppointment::query()
                ->where('booking_department_id', $bookingDepartmentId)
                ->whereDate('starts_at', $date->toDateString())
                ->notCancelled()
                ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
                ->pluck('starts_at')
                ->filter()
                ->map(fn ($value) => Carbon::parse((string) $value)->format('Y-m-d H:i:s'))
                ->flip();
        }

        $options = [];
        $disabled = [];

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($date->toDateString().' '.$schedule->start_time);
            $end = Carbon::parse($date->toDateString().' '.$schedule->end_time);

            while ($start->copy()->addMinutes($duration)->lte($end)) {
                $slotValue = $start->format('Y-m-d H:i:s');
                $options[$slotValue] = $start->format('H:i');
                $disabled[$slotValue] = $reserved->has($slotValue);
                $start->addMinutes($duration);
            }
        }

        return [
            'options' => $options,
            'disabled' => $disabled,
            'duration' => $duration,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getDisabledDatesForDepartment(int $bookingDepartmentId, CarbonInterface $from, CarbonInterface $to): array
    {
        if (! Schema::hasTable('booking_department_schedules')) {
            return [];
        }

        $from = Carbon::instance($from->toDateTime())->startOfDay();
        $to = Carbon::instance($to->toDateTime())->endOfDay();

        $activeDays = BookingDepartmentSchedule::query()
            ->where('booking_department_id', $bookingDepartmentId)
            ->where('is_active', true)
            ->pluck('day_of_week')
            ->map(fn ($day) => (int) $day)
            ->unique();

        if ($activeDays->isEmpty()) {
            return self::allDatesDisabled($from->copy()->startOfDay(), $to->copy()->endOfDay());
        }

        $day = $from->copy();
        $end = $to->copy();
        $disabledDates = [];

        while ($day->lte($end)) {
            if (! $activeDays->contains($day->dayOfWeekIso)) {
                $disabledDates[] = $day->toDateString();
                $day->addDay();

                continue;
            }

            $slots = self::buildSlotsForDepartmentDate($bookingDepartmentId, $day->copy());

            if (empty($slots['options']) || count(array_filter($slots['disabled'])) === count($slots['options'])) {
                $disabledDates[] = $day->toDateString();
            }

            $day->addDay();
        }

        return $disabledDates;
    }

    /**
     * @return array<int, string>
     */
    private static function allDatesDisabled(CarbonInterface $start, CarbonInterface $end): array
    {
        $start = Carbon::instance($start->toDateTime())->startOfDay();
        $end = Carbon::instance($end->toDateTime())->endOfDay();
        $dates = [];

        while ($start->lte($end)) {
            $dates[] = $start->toDateString();
            $start->addDay();
        }

        return $dates;
    }
}
