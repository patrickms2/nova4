<?php

namespace App\Mcp\Tools\CasaElPatio;

use App\Models\RentalReservation;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Genera un informe mensual de reservas de Casa El Patio: ingresos, noches, comisiones, gastos de limpieza y desglose por estado y propiedad.')]
class MonthlyReservationsReportTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'rental_property_id' => 'nullable|integer|exists:rental_properties,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $year = $validated['year'] ?? now()->year;
        $month = $validated['month'] ?? now()->month;
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $query = RentalReservation::query()
            ->with(['rentalProperty:id,name', 'guest:id,first_name,last_name,email'])
            ->whereBetween('check_in', [$start->toDateString(), $end->toDateString()]);

        if ($validated['rental_property_id'] ?? null) {
            $query->where('rental_property_id', $validated['rental_property_id']);
        }

        $reservas = $query->orderBy('check_in')->orderBy('id')->get();

        $listado = $reservas->map(fn (RentalReservation $r): array => [
            'id' => $r->id,
            'reference_code' => $r->reference_code,
            'property' => $r->rentalProperty?->name,
            'guest' => $r->guest?->fullName(),
            'guest_email' => $r->guest?->email,
            'check_in' => $r->check_in?->toDateString(),
            'check_out' => $r->check_out?->toDateString(),
            'nights' => $r->nights(),
            'amount' => (float) $r->amount,
            'channel_commission' => (float) $r->channel_commission,
            'management_commission' => (float) $r->management_commission,
            'cleaning_fee' => (float) $r->cleaning_fee,
            'payout' => (float) $r->payout,
            'status' => $r->status,
            'channel' => $r->channel,
        ]);

        $confirmadas = $reservas->where('status', 'confirmed');
        $totalNoches = $confirmadas->sum(fn (RentalReservation $r): int => $r->nights());

        return Response::json([
            'period' => [
                'year' => $year,
                'month' => $month,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'summary' => [
                'total_reservations' => $reservas->count(),
                'confirmed_reservations' => $confirmadas->count(),
                'total_amount' => (float) $reservas->sum('amount'),
                'confirmed_amount' => (float) $confirmadas->sum('amount'),
                'total_nights' => $totalNoches,
                'total_payout' => (float) $reservas->sum('payout'),
                'total_cleaning_fees' => (float) $reservas->sum('cleaning_fee'),
                'total_channel_commission' => (float) $reservas->sum('channel_commission'),
                'total_management_commission' => (float) $reservas->sum('management_commission'),
                'avg_nightly_rate' => $totalNoches > 0
                    ? round((float) $confirmadas->sum('amount') / $totalNoches, 2)
                    : 0.0,
                'by_status' => $reservas->groupBy('status')->map(fn ($items) => [
                    'count' => $items->count(),
                    'amount' => (float) $items->sum('amount'),
                ]),
                'by_property' => $reservas->groupBy('rental_property_id')->map(fn ($items) => [
                    'property' => $items->first()->rentalProperty?->name,
                    'count' => $items->count(),
                    'amount' => (float) $items->sum('amount'),
                    'nights' => $items->sum(fn (RentalReservation $r): int => $r->nights()),
                ])->values(),
            ],
            'reservations' => $listado
                ->when($validated['limit'] ?? null, fn ($items, int $limit) => $items->take($limit))
                ->values(),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'year' => $schema->integer()->description('Año del informe. Por defecto el año actual.'),
            'month' => $schema->integer()->description('Mes del informe (1-12). Por defecto el mes actual.'),
            'rental_property_id' => $schema->integer()->description('Filtrar por ID de propiedad.'),
            'limit' => $schema->integer()->description('Número máximo de reservas incluidas en el detalle (1-100). Por defecto todas.'),
        ];
    }
}
