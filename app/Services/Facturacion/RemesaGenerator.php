<?php

namespace App\Services\Facturacion;

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Factura;
use App\Models\Remesa;
use App\Models\RemesaCliente;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class RemesaGenerator
{
    /**
     * Genera las facturas para los clientes de una remesa.
     *
     * @return array{created: int, skipped: int, errors: array<int, string>}
     */
    public function generate(Remesa $remesa): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        $remesaClientes = $remesa->remesaClientes()
            ->with('cliente')
            ->get();

        foreach ($remesaClientes as $remesaCliente) {
            $cliente = $remesaCliente->cliente;

            if (! $cliente) {
                $skipped++;
                $errors[$remesaCliente->id] = 'Cliente no encontrado.';
                continue;
            }

            if ($remesaCliente->factura_id) {
                $skipped++;
                continue;
            }

            if (! $cliente->recurrencia_activa) {
                $skipped++;
                continue;
            }

            $conceptos = $this->conceptosRecurrentes($cliente);

            if ($conceptos->isEmpty()) {
                $skipped++;
                $errors[$remesaCliente->id] = 'El cliente no tiene conceptos recurrentes.';
                continue;
            }

            try {
                DB::transaction(function () use ($remesa, $remesaCliente, $cliente, $conceptos, &$created): void {
                    $factura = $this->crearFactura($remesa, $cliente, $conceptos);
                    $remesaCliente->update(['factura_id' => $factura->id]);
                    $created++;
                });
            } catch (Throwable $e) {
                $errors[$remesaCliente->id] = $e->getMessage();
            }
        }

        $remesa->markAsGenerated();

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @return Collection<int, Concepto>
     */
    private function conceptosRecurrentes(Cliente $cliente): Collection
    {
        return Concepto::query()
            ->where('cliente_id', $cliente->id)
            ->where('recurrente', true)
            ->orderBy('concepto')
            ->get();
    }

    /**
     * @param Collection<int, Concepto> $conceptos
     */
    private function crearFactura(Remesa $remesa, Cliente $cliente, Collection $conceptos): Factura
    {

        $lineas = $this->buildLineas($remesa,$conceptos);
        $totales = $this->calcularTotales($lineas);
$lineas = $totales['lineas'];

        $factura = new Factura;
        $factura->empresa_id = $cliente->empresa_id;
        $factura->remesa_id = $remesa->id;
        $factura->cliente_id = $cliente->id;
        $factura->cliente_nombre = $cliente->nombrecorto;
        $factura->cliente_cif = $cliente->dni;
        $factura->cliente_direccion = $cliente->direccion;
        $factura->cliente_telefono = $cliente->telefono;
        $factura->fechaemitido = $remesa->fecha;
        $factura->baseimponible = $totales['baseimponible'];
        $factura->baseexenta = 0;
        $factura->impuesto = $totales['impuesto'];
        $factura->retenciones = $totales['retenciones'];
        $factura->importe = $totales['importe'];
        $factura->notas = $remesa->notas;
        $factura->observaciones = $cliente->observaciones;
        $factura->save();
        foreach ($lineas as $linea) {
            $factura->registros()->create($linea);
        }

        return $factura;
    }

    /**
     * @param Collection<int, Concepto> $conceptos
     * @return array<int, array<string, mixed>>
     */
    private function buildLineas(
        Remesa $remesa,
        Collection $conceptos): array
    {
        return $conceptos->map(fn (Concepto $concepto): array => [
            'concepto_id' => $concepto->id,
            'unidad' => $concepto->unidad ?? 'ud',
            'descripcion' => $remesa->nombre,
            'cantidad' => 1,
            'precio' => (float) $concepto->precio,
            'descuento' => (float) $concepto->descuento,
            'impuesto' => (float) $concepto->impuesto,
            'retenciones' => (float) $concepto->retenciones,
        ])->all();
    }

    /**
     * @param array<int, array<string, mixed>> $lineas
     * @return array<string, float>
     */
    private function calcularTotales(array $lineas): array
    {
        $base = 0;
        $igic = 0;
        $ret = 0;
        $importe = 0;

        foreach ($lineas as &$linea) {
            $cantidad = (float) ($linea['cantidad'] ?? 0);
            $precio = (float) ($linea['precio'] ?? 0);
            $dto = (float) ($linea['descuento'] ?? 0);
            $tipoI = (float) ($linea['impuesto'] ?? 7);
            $tipoR = (float) ($linea['retenciones'] ?? 15);

            $bruto = $cantidad * $precio;
            $importeDto = $bruto * ($dto / 100);
            $baseLinea = $bruto - $importeDto;

            $valorI = $baseLinea * ($tipoI / 100);
            $valorR = $baseLinea * ($tipoR / 100);

            $linea['valorimpuesto'] = round($valorI, 2);
            $linea['valorretenciones'] = round($valorR, 2);
            $linea['importe'] = round($baseLinea + $valorI - $valorR, 2);
            $linea['fecha'] = now();

            $importe += $linea['importe'];
            $base += $baseLinea;
            $igic += $valorI;
            $ret += $valorR;
        }

        return [
            'lineas' => $lineas,
            'baseimponible' => round($base, 2),
            'impuesto' => round($igic, 2),
            'retenciones' => round($ret, 2),
            'importe' => round($importe, 2),
        ];
    }
}
