<?php

namespace App\Services\Bookings;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class BookingRepository
{
    public function getByDateRange(Carbon $start, Carbon $end, array $filters = []): Collection
    {
        return $this->baseQuery($filters)
            ->whereBetween('booking_starts_at', [$start, $end])
            ->orderBy('booking_starts_at')
            ->get();
    }

    public function getCalendarData(Carbon $month, array $filters = []): Collection
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return $this->baseQuery($filters)
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('booking_date, COUNT(*) as total_reservas, SUM(attendees) as total_asistentes, SUM(total) as total_facturado')
            ->groupBy('booking_date')
            ->orderBy('booking_date')
            ->get();
    }

    public function getStats(Carbon $day): array
    {
        $query = Reservation::query()->whereDate('booking_starts_at', $day->toDateString());

        return [
            'reservas_hoy' => (clone $query)->count(),
            'ingresos_hoy' => (float) ((clone $query)->sum('total') ?: 0),
            'pendientes_hoy' => (clone $query)->where('payment_status', '!=', 'paid')->count(),
            'incidencias_hoy' => (clone $query)->where('has_incident', true)->count(),
        ];
    }

    private function baseQuery(array $filters = []): Builder
    {
        return Reservation::query()
            ->when(filled($filters['service_name'] ?? null), fn (Builder $query): Builder => $query->where('service_name', $filters['service_name']))
            ->when(filled($filters['language_code'] ?? null), fn (Builder $query): Builder => $query->where('language_code', $filters['language_code']))
            ->when(filled($filters['agent_name'] ?? null), fn (Builder $query): Builder => $query->where('agent_name', $filters['agent_name']))
            ->when(filled($filters['booking_status'] ?? null), fn (Builder $query): Builder => $query->where('booking_status', $filters['booking_status']))
            ->when(filled($filters['payment_status'] ?? null), fn (Builder $query): Builder => $query->where('payment_status', $filters['payment_status']));
    }
}
