<?php

namespace App\Livewire\Facturas;

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Empresa;
use Livewire\Component;

class Clientes extends Component
{
    public string $search = '';

    public string $viewMode = 'table';

    public ?int $editingId = null;

    public ?int $editingConceptoId = null;
    public ?int $tipoFilter = null;
    public ?int $clienteFilter = null;
    public ?int $empresaFilter = null;

    public ?string $statusFilter = null;
    public $clientes = [];

    public array $conceptos = [];

    public $empresas = [];
    public $tipos = [];

    public array $form = [
        'nombretotal' => '',
        'dni' => '',
        'email' => '',
        'telefono' => '',
        'domicilio' => '',
        'poblacion' => '',
        'codigopostal' => '',
                'tipo' => null,

        'empresa_id' => null,
        'domiciliado' => false,
        'observaciones' => '',
    ];

    public array $conceptoForm = [
        'concepto' => '',
        'unidad' => 'UNID',
        'precio' => 0,
        'descuento' => 0,
        'impuesto' => 7,
        'retenciones' => 15,
        'categoria' => '',
        'observaciones' => '',
        'recurrente' => false,
    ];

    public array $recurrencia = [
        'cliente_id' => null,
        'dia' => 1,
        'activa' => false,
        'notas' => '',
    ];



    public function clearFilters(): void
    {
        $this->search = '';
        $this->empresaFilter = null;
        $this->tipoFilter = null;
    }

    public function mount(): void
    {
        $this->init();
    }

    public function init()
    {
        $this->clientes = $this->getClientes();
        $this->empresas = $this->getEmpresas();
        }
    public function getClientes()
    {
        return Cliente::query()
                    ->with(['empresa'])
    ->when($this->empresaFilter, fn ($q) => $q->where('empresa_id', $this->empresaFilter))
            ->when($this->tipoFilter, fn ($q) => $q->where('tipo', $this->tipoFilter))
            ->when($this->search, fn ($q) => $q
                ->where('nombretotal', 'like', "%{$this->search}%")
                ->where('nombre_empresa', 'like', "%{$this->search}%")
                ->orWhere('dni', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->withCount(['facturas', 'conceptos'])
            ->orderBy('nombretotal')
            ->get();
    }

    public function getEmpresas()
    {
        return Empresa::orderBy('empresa')->get(['id', 'empresa','nombre']);
    }

    public function openEdit(int $id): void
    {
        $cliente = Cliente::findOrFail($id);
        $this->editingId = $id;
        $this->form = [
            'nombretotal' => $cliente->nombretotal ?? '',
            'dni' => $cliente->dni ?? '',
            'email' => $cliente->email ?? '',
            'telefono' => $cliente->telefono ?? '',
            'domicilio' => $cliente->domicilio ?? '',
            'poblacion' => $cliente->poblacion ?? '',
            'codigopostal' => $cliente->codigopostal ?? '',
            'empresa_id' => $cliente->empresa_id,
            'domiciliado' => (bool) $cliente->domiciliado,
            'observaciones' => $cliente->observaciones ?? '',
        ];
    }
public function deleteCliente(int $id): void
    {
        Cliente::findOrFail($id)->delete();

        $this->editingId = null;
        $this->dispatch('notify', type: 'success', message: 'Cliente eliminado.');
    }

    public function saveCliente(): void
    {
        $this->validate([
            'form.nombretotal' => 'required|string|max:150',
            'form.dni' => 'nullable|string|max:20',
            'form.email' => 'nullable|email|max:255',
            'form.telefono' => 'nullable|string|max:20',
            'form.domicilio' => 'nullable|string|max:255',
            'form.poblacion' => 'nullable|string|max:100',
            'form.codigopostal' => 'nullable|string|max:10',
            'form.empresa_id' => 'nullable|exists:empresas,id',
            'form.domiciliado' => 'boolean',
            'form.observaciones' => 'nullable|string',
        ]);

        Cliente::findOrFail($this->editingId)->update($this->form);

        $this->editingId = null;
        $this->dispatch('notify', type: 'success', message: 'Cliente actualizado.');
    }

    public function openEditConcepto(int $conceptoId): void
    {
        $concepto = Concepto::findOrFail($conceptoId);
        $this->editingConceptoId = $conceptoId;
        $this->conceptoForm = [
            'concepto' => $concepto->concepto ?? '',
            'unidad' => $concepto->unidad ?? 'UNID',
            'precio' => $concepto->precio ?? 0,
            'descuento' => $concepto->descuento ?? 0,
            'impuesto' => $concepto->impuesto ?? 7,
            'retenciones' => $concepto->retenciones ?? 15,
            'categoria' => $concepto->categoria ?? '',
            'observaciones' => $concepto->observaciones ?? '',
            'recurrente' => (bool) $concepto->recurrente,
        ];
    }

    public function saveConcepto(): void
    {
        $this->validate([
            'conceptoForm.concepto' => 'required|string|max:255',
            'conceptoForm.unidad' => 'required|string|max:20',
            'conceptoForm.precio' => 'required|numeric|min:0',
            'conceptoForm.descuento' => 'required|numeric|min:0|max:100',
            'conceptoForm.impuesto' => 'required|numeric|min:0|max:100',
            'conceptoForm.retenciones' => 'required|numeric|min:0|max:100',
            'conceptoForm.categoria' => 'nullable|string|max:50',
            'conceptoForm.observaciones' => 'nullable|string',
            'conceptoForm.recurrente' => 'boolean',
        ]);

        Concepto::findOrFail($this->editingConceptoId)->update($this->conceptoForm);

        $this->editingConceptoId = null;
        $this->dispatch('notify', type: 'success', message: 'Concepto actualizado.');
    }

    public function deleteConcepto(int $conceptoId): void
    {
        Concepto::findOrFail($conceptoId)->delete();
        $this->dispatch('notify', type: 'success', message: 'Concepto eliminado.');
    }

    public function openRecurrencia(int $clienteId): void
    {
        $cliente = Cliente::findOrFail($clienteId);
        $this->recurrencia = [
            'cliente_id' => $clienteId,
            'dia' => (int) ($cliente->recurrencia_dia ?? 1),
            'activa' => (bool) $cliente->recurrencia_activa,
            'notas' => $cliente->recurrencia_notas ?? '',
        ];
    }

    public function saveRecurrencia(): void
    {
        $this->validate([
            'recurrencia.cliente_id' => 'required|exists:clientes,id',
            'recurrencia.dia' => 'required|integer|min:1|max:28',
            'recurrencia.activa' => 'boolean',
            'recurrencia.notas' => 'nullable|string|max:255',
        ]);

        Cliente::findOrFail($this->recurrencia['cliente_id'])->update([
            'recurrencia_dia' => $this->recurrencia['dia'],
            'recurrencia_activa' => $this->recurrencia['activa'],
            'recurrencia_notas' => $this->recurrencia['notas'] ?? null,
        ]);

        $this->dispatch('notify', type: 'success', message: 'Facturación recurrente actualizada.');
    }

    public function render()
    {
        return view('livewire.facturacion.clientes')
            ->layout('layouts.front');
    }
}
