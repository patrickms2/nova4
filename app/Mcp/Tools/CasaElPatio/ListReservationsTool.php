<?php

namespace App\Mcp\Tools\CasaElPatio;

use App\Models\RentalReservation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista reservas de Casa El Patio con filtros por propiedad, estado, fechas de entrada/salida y búsqueda por huésped. Devuelve un resumen en JSON.')]
class ListReservationsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'rental_property_id' => 'nullable|integer|exists:rental_properties,id',
            'status' => 'nullable|string',
            'check_in_desde' => 'nullable|date',
            'check_in_hasta' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $reservas = RentalReservation::query()
            ->with(['rentalProperty:id,name', 'guest:id,first_name,last_name,email,phone'])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference_code', 'like', "%{$search}%")
                        ->orWhereHas('guest', function ($query) use ($search): void {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('rentalProperty', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($validated['rental_property_id'] ?? null, fn ($query, int $id) => $query->where('rental_property_id', $id))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['check_in_desde'] ?? null, fn ($query, string $desde) => $query->whereDate('check_in', '>=', $desde))
            ->when($validated['check_in_hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('check_in', '<=', $hasta))
            ->orderByDesc('check_in')
            ->orderByDesc('id')
            ->limit($validated['limit'] ?? 10)
            ->get();

        return Response::json([
            'count' => $reservas->count(),
            'reservas' => $reservas->map(fn (RentalReservation $r): array => [
                'id' => $r->id,
                'reference_code' => $r->reference_code,
                'property_id' => $r->rental_property_id,
                'property' => $r->rentalProperty?->name,
                'guest_id' => $r->guest_id,
                'guest' => $r->guest?->fullName(),
                'guest_email' => $r->guest?->email,
                'guest_phone' => $r->guest?->phone,
                'check_in' => $r->check_in?->toDateString(),
                'check_out' => $r->check_out?->toDateString(),
                'adults' => $r->adults,
                'children' => $r->children,
                'amount' => (float) $r->amount,
                'status' => $r->status,
                'channel' => $r->channel,
            ])->values(),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Texto a buscar en referencia, huésped o propiedad.'),
            'rental_property_id' => $schema->integer()->description('Filtrar por ID de propiedad.'),
            'status' => $schema->string()->description('Estado de la reserva (pending, confirmed, checked_in, checked_out, cancelled).'),
            'check_in_desde' => $schema->string()->description('Fecha mínima de entrada (Y-m-d).'),
            'check_in_hasta' => $schema->string()->description('Fecha máxima de entrada (Y-m-d).'),
            'limit' => $schema->integer()->description('Número máximo de reservas (1-100). Por defecto 10.'),
        ];
    }
}
