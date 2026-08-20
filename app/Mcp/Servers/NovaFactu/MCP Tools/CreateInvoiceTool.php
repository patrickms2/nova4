<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Factura;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crea una factura en NovaFact para un cliente. El cliente puede indicarse por ID o por nombre. Si no se pasan líneas, se generan automáticamente a partir de los conceptos del cliente (precio, descuento, IGIC y retenciones del concepto). La descripción de cada línea por defecto es el mes anterior a la fecha de emisión (p. ej. factura de julio → "Junio"). Calcula automáticamente los totales y asigna el número de factura del contador anual. Devuelve la factura creada en JSON.')]
class CreateInvoiceTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'cliente' => 'nullable|string',
            'fechaemitido' => 'nullable|date',
            'remesa_id' => 'nullable|integer|exists:remesas,id',
            'notas' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'lineas' => 'nullable|array',
            'lineas.*.descripcion' => 'nullable|string',
            'lineas.*.cantidad' => 'nullable|numeric|min:0.01',
            'lineas.*.precio' => 'nullable|numeric|min:0',
            'lineas.*.descuento' => 'nullable|numeric|min:0',
            'lineas.*.impuesto' => 'nullable|numeric|min:0',
            'lineas.*.retenciones' => 'nullable|numeric|min:0',
            'lineas.*.concepto_id' => 'nullable|integer|exists:conceptos,id',
            'lineas.*.unidad' => 'nullable|string',
        ]);

        $cliente = $this->resolveCliente($validated);

        if (! $cliente instanceof Cliente) {
            return $cliente;
        }

        $fecha = Carbon::parse($validated['fechaemitido'] ?? now()->toDateString());
        $descripcionDefault = $this->descripcionPorDefecto($fecha);

        $lineasBase = $this->resolverLineas($validated['lineas'] ?? [], $cliente, $descripcionDefault);

        if (! is_array($lineasBase)) {
            return $lineasBase;
        }

        $lineas = $this->calcularLineas($lineasBase);
        $totales = $this->calcularTotales($lineas);

        $factura = DB::transaction(function () use ($validated, $cliente, $fecha, $lineas, $totales): Factura {
            $factura = new Factura;
            $factura->cliente_id = $cliente->id;
            $factura->empresa_id = $cliente->empresa_id;
            $factura->remesa_id = $validated['remesa_id'] ?? null;
            $factura->fechaemitido = $fecha;
            $factura->baseimponible = $totales['baseimponible'];
            $factura->baseexenta = 0;
            $factura->impuesto = $totales['impuesto'];
            $factura->retenciones = $totales['retenciones'];
            $factura->importe = $totales['importe'];
            $factura->notas = $validated['notas'] ?? null;
            $factura->observaciones = $validated['observaciones'] ?? null;
            $factura->save();

            foreach ($lineas as $linea) {
                $factura->registros()->create([
                    'concepto_id' => $linea['concepto_id'],
                    'descripcion' => $linea['descripcion'],
                    'cantidad' => $linea['cantidad'],
                    'unidad' => $linea['unidad'],
                    'precio' => $linea['precio'],
                    'descuento' => $linea['descuento'],
                    'impuesto' => $linea['impuesto'],
                    'retenciones' => $linea['retenciones'],
                    'valorimpuesto' => $linea['valorimpuesto'],
                    'valorretenciones' => $linea['valorretenciones'],
                    'importe' => $linea['importe'],
                    'fecha' => $fecha,
                ]);
            }

            return $factura;
        });

        return Response::json([
            'id' => $factura->id,
            'codfactura' => $factura->codfactura,
            'cliente_id' => $factura->cliente_id,
            'cliente' => $factura->cliente?->nombretotal,
            'fechaemitido' => $factura->fechaemitido->toDateString(),
            'baseimponible' => (float) $factura->baseimponible,
            'impuesto' => (float) $factura->impuesto,
            'retenciones' => (float) $factura->retenciones,
            'importe' => (float) $factura->importe,
            'lineas' => count($lineas),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'cliente_id' => $schema->integer()->description('ID del cliente al que se emite la factura. Alternativa a "cliente".'),
            'cliente' => $schema->string()->description('Nombre (o parte del nombre) del cliente. Alternativa a "cliente_id".'),
            'fechaemitido' => $schema->string()->description('Fecha de emisión en formato Y-m-d. Por defecto, hoy.'),
            'remesa_id' => $schema->integer()->description('ID de la remesa a la que asociar la factura (opcional).'),
            'notas' => $schema->string()->description('Notas internas de la factura.'),
            'observaciones' => $schema->string()->description('Observaciones visibles de la factura.'),
            'lineas' => $schema->array()->items(
                $schema->object([
                    'descripcion' => $schema->string()->description('Descripción de la línea. Por defecto, el mes anterior a la fecha de emisión (p. ej. "Junio").'),
                    'cantidad' => $schema->number()->description('Cantidad. Por defecto 1.'),
                    'precio' => $schema->number()->description('Precio unitario. Por defecto, el del concepto asociado.'),
                    'descuento' => $schema->number()->description('Descuento en porcentaje. Por defecto, el del concepto o 0.'),
                    'impuesto' => $schema->number()->description('Tipo de IGIC en porcentaje. Por defecto, el del concepto o 7.'),
                    'retenciones' => $schema->number()->description('Tipo de retención en porcentaje. Por defecto, el del concepto o 0.'),
                    'concepto_id' => $schema->integer()->description('ID de concepto asociado; sus valores se usan como defaults de la línea.'),
                    'unidad' => $schema->string()->description('Unidad de medida. Por defecto, la del concepto o UNID.'),
                ])
            )->description('Líneas de la factura. Si se omiten, se generan a partir de los conceptos del cliente.'),
        ];
    }

    /**
     * Resolve the client from its ID or a name search.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveCliente(array $validated): Cliente|Response
    {
        if ($validated['cliente_id'] ?? null) {
            return Cliente::query()->findOrFail($validated['cliente_id']);
        }

        $nombre = trim((string) ($validated['cliente'] ?? ''));

        if ($nombre === '') {
            return Response::error('Debes indicar cliente_id o cliente (nombre).');
        }

        $clientes = Cliente::query()
            ->where('nombretotal', 'like', "%{$nombre}%")
            ->limit(5)
            ->get();

        if ($clientes->isEmpty()) {
            return Response::error("No se encontró ningún cliente que coincida con \"{$nombre}\".");
        }

        if ($clientes->count() > 1) {
            $opciones = $clientes->map(fn (Cliente $c): string => "{$c->id}: {$c->nombretotal}")->implode('; ');

            return Response::error("Hay varios clientes que coinciden con \"{$nombre}\". Usa cliente_id: {$opciones}");
        }

        return $clientes->first();
    }

    /**
     * Build the default line description: the month before the issue date.
     */
    private function descripcionPorDefecto(Carbon $fecha): string
    {
        return ucfirst($fecha->copy()->subMonthNoOverflow()->locale('es')->translatedFormat('F'));
    }

    /**
     * Resolve the invoice lines, defaulting from the client's conceptos.
     *
     * @param  array<int, array<string, mixed>>  $lineas
     * @return array<int, array<string, mixed>>|Response
     */
    private function resolverLineas(array $lineas, Cliente $cliente, string $descripcionDefault): array|Response
    {
        if ($lineas === []) {
            $conceptos = Concepto::query()
                ->where('cliente_id', $cliente->id)
                ->orderBy('concepto')
                ->get();

            if ($conceptos->isEmpty()) {
                return Response::error("El cliente {$cliente->nombretotal} no tiene conceptos definidos. Indica las líneas manualmente.");
            }

            return $conceptos->map(fn (Concepto $concepto): array => [
                'concepto_id' => $concepto->id,
                'descripcion' => $descripcionDefault,
                'cantidad' => 1,
                'unidad' => $concepto->unidad ?? 'UNID',
                'precio' => (float) ($concepto->precio ?? 0),
                'descuento' => (float) ($concepto->descuento ?? 0),
                'impuesto' => (float) ($concepto->impuesto ?? 7),
                'retenciones' => (float) ($concepto->retenciones ?? 0),
            ])->all();
        }

        $resueltas = [];

        foreach ($lineas as $i => $linea) {
            $concepto = ($linea['concepto_id'] ?? null) ? Concepto::find($linea['concepto_id']) : null;

            $precio = $linea['precio'] ?? $concepto?->precio;

            if ($precio === null) {
                return Response::error('La línea '.($i + 1).' no tiene precio ni concepto del que tomarlo.');
            }

            $resueltas[] = [
                'concepto_id' => $concepto?->id,
                'descripcion' => $linea['descripcion'] ?? $concepto?->concepto ?? $descripcionDefault,
                'cantidad' => $linea['cantidad'] ?? 1,
                'unidad' => $linea['unidad'] ?? $concepto?->unidad ?? 'UNID',
                'precio' => (float) $precio,
                'descuento' => $linea['descuento'] ?? $concepto?->descuento ?? 0,
                'impuesto' => $linea['impuesto'] ?? $concepto?->impuesto ?? 7,
                'retenciones' => $linea['retenciones'] ?? $concepto?->retenciones ?? 0,
            ];
        }

        return $resueltas;
    }

    /**
     * Normalize the invoice lines and compute per-line amounts.
     *
     * @param  array<int, array<string, mixed>>  $lineas
     * @return array<int, array<string, mixed>>
     */
    private function calcularLineas(array $lineas): array
    {
        return array_map(function (array $linea): array {
            $cantidad = (float) ($linea['cantidad'] ?? 1);
            $precio = (float) $linea['precio'];
            $descuento = (float) ($linea['descuento'] ?? 0);
            $impuesto = (float) ($linea['impuesto'] ?? 7);
            $retenciones = (float) ($linea['retenciones'] ?? 0);

            $base = $cantidad * $precio * (1 - $descuento / 100);
            $valorImpuesto = $base * ($impuesto / 100);
            $valorRetenciones = $base * ($retenciones / 100);

            return [
                'concepto_id' => $linea['concepto_id'] ?? null,
                'descripcion' => $linea['descripcion'],
                'cantidad' => $cantidad,
                'unidad' => $linea['unidad'] ?? 'UNID',
                'precio' => $precio,
                'descuento' => $descuento,
                'impuesto' => $impuesto,
                'retenciones' => $retenciones,
                'valorimpuesto' => round($valorImpuesto, 2),
                'valorretenciones' => round($valorRetenciones, 2),
                'importe' => round($base + $valorImpuesto - $valorRetenciones, 2),
                'base' => $base,
            ];
        }, $lineas);
    }

    /**
     * Compute the invoice totals from the calculated lines.
     *
     * @param  array<int, array<string, mixed>>  $lineas
     * @return array{baseimponible: float, impuesto: float, retenciones: float, importe: float}
     */
    private function calcularTotales(array $lineas): array
    {
        $base = array_sum(array_column($lineas, 'base'));
        $impuesto = array_sum(array_column($lineas, 'valorimpuesto'));
        $retenciones = array_sum(array_column($lineas, 'valorretenciones'));

        return [
            'baseimponible' => round($base, 2),
            'impuesto' => round($impuesto, 2),
            'retenciones' => round($retenciones, 2),
            'importe' => round($base + $impuesto - $retenciones, 2),
        ];
    }
}
