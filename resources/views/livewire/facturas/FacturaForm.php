<?php

namespace App\Http\Livewire\Facturas;

use App\Models\Factura;
use Livewire\Component;

class FacturaForm extends Component
{
    public ?int $facturaId = null;

    public ?int $cliente_id = null;

    public int $empresa_id = 1;

    public string $numero = '';

    public string $serie = 'A';

    public ?string $fecha = null;

    public ?string $notas = null;

    public array $lineas = [];

    public float $subtotal = 0;

    public float $total_igic = 0;

    public float $total_retencion = 0;

    public float $total_factura = 0;

    protected $listeners = [
        'clienteSelected' => 'setCliente',
        'conceptoSelectedForLinea' => 'setConceptoLinea',
    ];

    public function mount(?int $facturaId = null): void
    {
        $this->facturaId = $facturaId;

        $this->fecha = now()->format('Y-m-d');

        if ($facturaId) {
            // Carga de factura existente pendiente de implementar
        } else {
            $this->lineas = [
                $this->nuevaLinea(),
            ];
            $this->recalcularTotales();
        }
    }

    protected function nuevaLinea(): array
    {
        return [
            'concepto_id' => null,
            'descripcion' => '',
            'cantidad' => 1,
            'unidad' => 'ud',
            'precio' => 0.0,
            'descuento' => 0.0,
            'igic' => 7.0,
            'retencion' => 15.0,
            'base' => 0.0,
            'total' => 0.0,
        ];
    }

    public function addLinea(): void
    {
        $this->lineas[] = $this->nuevaLinea();
    }

    public function removeLinea(int $index): void
    {
        unset($this->lineas[$index]);
        $this->lineas = array_values($this->lineas);
        $this->recalcularTotales();
    }

    public function updatedLineas($value, $name): void
    {
        // $name = "lineas.2.cantidad"
        $parts = explode('.', $name);
        if (count($parts) >= 3) {
            $index = (int) $parts[1];
            $this->recalcularLinea($index);
        }
    }

    public function setCliente(?int $clienteId): void
    {
        $this->cliente_id = $clienteId;
    }

    public function setConceptoLinea(int $lineIndex, $conceptoId, $precio, $unidad, $descripcion): void
    {
        if (! isset($this->lineas[$lineIndex])) {
            return;
        }

        $linea = &$this->lineas[$lineIndex];

        $linea['concepto_id'] = $conceptoId;
        if ($descripcion) {
            $linea['descripcion'] = $descripcion;
        }
        if ($unidad) {
            $linea['unidad'] = $unidad;
        }
        if ($precio !== null) {
            $linea['precio'] = (float) $precio;
        }
        if (! $linea['cantidad']) {
            $linea['cantidad'] = 1;
        }

        $this->recalcularLinea($lineIndex);
    }

    public function recalcularLinea(int $index): void
    {
        if (! isset($this->lineas[$index])) {
            return;
        }

        $linea = &$this->lineas[$index];

        $cantidad = (float) ($linea['cantidad'] ?? 0);
        $precio = (float) ($linea['precio'] ?? 0);
        $descuento = (float) ($linea['descuento'] ?? 0);
        $igic = (float) ($linea['igic'] ?? 7);
        $retencion = (float) ($linea['retencion'] ?? 15);

        $base = $cantidad * $precio;

        if ($descuento > 0) {
            $base -= $base * ($descuento / 100);
        }

        $importeIgic = $base * $igic / 100;
        $importeRet = $base * $retencion / 100;
        $total = $base + $importeIgic - $importeRet;

        $linea['base'] = round($base, 2);
        $linea['total'] = round($total, 2);

        $this->recalcularTotales();
    }

    public function recalcularTotales(): void
    {
        $subtotal = 0;
        $totalIgic = 0;
        $totalRet = 0;

        foreach ($this->lineas as $linea) {
            $base = (float) ($linea['base'] ?? 0);
            $igic = (float) ($linea['igic'] ?? 7);
            $ret = (float) ($linea['retencion'] ?? 15);

            $subtotal += $base;
            $totalIgic += $base * $igic / 100;
            $totalRet += $base * $ret / 100;
        }

        $this->subtotal = round($subtotal, 2);
        $this->total_igic = round($totalIgic, 2);
        $this->total_retencion = round($totalRet, 2);
        $this->total_factura = round($subtotal + $totalIgic - $totalRet, 2);
    }

    public function save(): void
    {
        // Aquí iría la lógica de guardado real (Factura + RegistrosFactura)
        // De momento solo validamos y emitimos evento.
        $this->validate([
            'cliente_id' => 'required|integer',
            'fecha' => 'required|date',
            'lineas' => 'array|min:1',
            'lineas.*.cantidad' => 'numeric|min:0',
            'lineas.*.precio' => 'numeric|min:0',
        ]);

        $this->recalcularTotales();

        $this->dispatch('facturaSaved', [
            'cliente_id' => $this->cliente_id,
            'total' => $this->total_factura,
        ]);
    }

    public function render()
    {
        return view('livewire.facturas.factura-form');
    }
}
