<?php

namespace App\Mcp\Tools\CasaElPatio;

use App\Models\RentalExpense;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista gastos de Casa El Patio con filtros por propiedad, categoría, estado, fechas y búsqueda por proveedor/descripción. Devuelve un resumen en JSON.')]
class ListExpensesTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'rental_property_id' => 'nullable|integer|exists:rental_properties,id',
            'category' => 'nullable|string',
            'status' => 'nullable|string',
            'expense_date_desde' => 'nullable|date',
            'expense_date_hasta' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $gastos = RentalExpense::query()
            ->with(['rentalProperty:id,name'])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('provider_name', 'like', "%{$search}%");
                });
            })
            ->when($validated['rental_property_id'] ?? null, fn ($query, int $id) => $query->where('rental_property_id', $id))
            ->when($validated['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['expense_date_desde'] ?? null, fn ($query, string $desde) => $query->whereDate('expense_date', '>=', $desde))
            ->when($validated['expense_date_hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('expense_date', '<=', $hasta))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->limit($validated['limit'] ?? 10)
            ->get();

        return Response::json([
            'count' => $gastos->count(),
            'gastos' => $gastos->map(fn (RentalExpense $e): array => [
                'id' => $e->id,
                'property_id' => $e->rental_property_id,
                'property' => $e->rentalProperty?->name,
                'category' => $e->category,
                'description' => $e->description,
                'provider_name' => $e->provider_name,
                'expense_date' => $e->expense_date?->toDateString(),
                'base_amount' => (float) $e->base_amount,
                'tax_amount' => (float) $e->tax_amount,
                'total_amount' => (float) $e->total_amount,
                'status' => $e->status,
                'is_recurrent' => (bool) $e->is_recurrent,
            ])->values(),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Texto a buscar en descripción o proveedor.'),
            'rental_property_id' => $schema->integer()->description('Filtrar por ID de propiedad.'),
            'category' => $schema->string()->description('Categoría del gasto (seguro, ibi, basura, agua, luz, internet, jardin, piscina, jacuzzi, amazon, reposiciones, reformas, mantenimiento, impuestos).'),
            'status' => $schema->string()->description('Estado del gasto (pending, paid, cancelled).'),
            'expense_date_desde' => $schema->string()->description('Fecha mínima del gasto (Y-m-d).'),
            'expense_date_hasta' => $schema->string()->description('Fecha máxima del gasto (Y-m-d).'),
            'limit' => $schema->integer()->description('Número máximo de gastos (1-100). Por defecto 10.'),
        ];
    }
}
