<?php

namespace App\Livewire\Facturas;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Concepto;
use App\Models\Category;
use App\Models\ContadorFactura;
use Livewire\Component;
use Illuminate\Support\Carbon;

class Ajustes extends Component
{
    public int $ano;

    public int $contador = 0;

    public string $search = '';

    public ?int $clienteFilter = null;

    public ?int $editingConceptoId = null;
    public ?int $editingCategoriaId = null;

    public array $conceptoForm = [
        'cliente_id' => null,
        'concepto' => '',
        'grupo' => '',
        'unidad' => 'UNID',
        'precio' => 0,
        'descuento' => 0,
        'impuesto' => 7,
        'retenciones' => 15,
        'unidadminimo' => 1,
        'observaciones' => '',
        'categoria' => '',
        'recurrente' => false,
    ];
    public array $categoriaForm = [
        'user_id' => null,
        'name' => '',
        'type' => '',
    ];
    public function mount(): void
    {
        $this->ano = now()->year;
        $this->loadContador();
    }
  public function getCategoriasProperty()
    {
        return Category::query()
            ->with('user')
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
            )
            ->when($this->clienteFilter, fn ($q) => $q->where('user_id', $this->clienteFilter))
            ->orderBy('name')
            ->get();

    }
    public function getConceptosProperty()
    {
        return Concepto::query()
            ->with('cliente')
            ->when($this->search, fn ($q) => $q
                ->where('concepto', 'like', "%{$this->search}%")
                ->orWhere('codconcepto', 'like', "%{$this->search}%")
            )
            ->when($this->clienteFilter, fn ($q) => $q->where('cliente_id', $this->clienteFilter))
            ->orderBy('concepto')
            ->get();
    }

    public function getClientesProperty()
    {
        return Cliente::orderBy('nombretotal')->get(['id', 'nombretotal']);
    }

     public function nuevaCategoria(): void
    {
        $this->dispatch('open-dialog-categoria-newform');
        $this->categoriaForm = [
            'user_id' => auth()->id(),
            'name' => $this->name ?? '',
            'type' => $this->type ?? '',
        ];
        $this->saveCategoria();


    }
     public function openEditCategoria(int $categoryId): void
    {
        $categoria = Category::findOrFail($categoryId);
        $this->editingCategoriaId = $categoryId;
        $this->categoriaForm = [
            'user_id' => $categoria->user_id,
            'name' => $categoria->name ?? '',
            'type' => $categoria->type ?? '',
        ];
    }
    public function createCategoria(): void
    {
        $this->validate([
            'categoriaForm.user_id' => 'required|exists:users,id',
            'categoriaForm.name' => 'required|string|max:255',
            'categoriaForm.type' => 'nullable|string|max:50',
        ]);

        Category::create($this->categoriaForm);

        $this->editingCategoriaId = null;
        $this->dispatch('notify', type: 'success', message: 'Categoría creada.');
    }

    private function mesEnEspanol(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $fecha = filled($this->form['date'])
            ? Carbon::parse($this->form['date'])
            : now();

        return $meses[$fecha->month - 1];
    }

    public function saveCategoria(): void
    {
        $this->validate([
            'categoriaForm.user_id' => 'required|exists:users,id',
            'categoriaForm.name' => 'required|string|max:255',
            'categoriaForm.type' => 'nullable|string|max:50',
        ]);

        Category::findOrFail($this->editingCategoriaId)->update($this->categoriaForm);

        $this->editingCategoriaId = null;
        $this->dispatch('notify', type: 'success', message: 'Categoría actualizada.');
    }

    public function deleteCategoria(int $categoryId): void
    {
        Category::findOrFail($categoryId)->delete();
        $this->dispatch('notify', type: 'success', message: 'Categoría eliminada.');
    }
    public function nuevoConcepto(): void
    {
        $this->editingConceptoId = null;
        $this->conceptoForm = [
            'cliente_id' => null,
            'concepto' => '',
            'grupo' => '',
            'unidad' => 'UNID',
            'precio' => 0,
            'descuento' => 0,
            'impuesto' => 7,
            'retenciones' => 15,
            'unidadminimo' => 1,
            'observaciones' => '',
            'categoria' => '',
            'recurrente' => false,
        ];

        $this->dispatch('open-dialog-concepto-form');
    }
    public function openEditConcepto(int $conceptoId): void
    {
        $concepto = Concepto::findOrFail($conceptoId);
        $this->editingConceptoId = $conceptoId;
        $this->conceptoForm = [
            'cliente_id' => $concepto->cliente_id,
            'concepto' => $concepto->concepto ?? '',
            'grupo' => $concepto->grupo ?? '',
            'unidad' => $concepto->unidad ?? 'UNID',
            'precio' => (float) ($concepto->precio ?? 0),
            'descuento' => (float) ($concepto->descuento ?? 0),
            'impuesto' => (float) ($concepto->impuesto ?? 7),
            'retenciones' => (float) ($concepto->retenciones ?? 15),
            'unidadminimo' => (float) ($concepto->unidadminimo ?? 1),
            'observaciones' => $concepto->observaciones ?? '',
            'categoria' => $concepto->categoria ?? '',
            'recurrente' => (bool) $concepto->recurrente,
        ];
    }

    public function saveConcepto(): void
    {
        $this->validate([
            'conceptoForm.cliente_id' => 'required|exists:clientes,id',
            'conceptoForm.concepto' => 'required|string|max:255',
            'conceptoForm.grupo' => 'nullable|string|max:50',
            'conceptoForm.unidad' => 'required|string|max:20',
            'conceptoForm.precio' => 'required|numeric|min:0',
            'conceptoForm.descuento' => 'required|numeric|min:0|max:100',
            'conceptoForm.impuesto' => 'required|numeric|min:0|max:100',
            'conceptoForm.retenciones' => 'required|numeric|min:0|max:100',
            'conceptoForm.unidadminimo' => 'nullable|numeric|min:0',
            'conceptoForm.observaciones' => 'nullable|string',
            'conceptoForm.categoria' => 'nullable|string|max:50',
            'conceptoForm.recurrente' => 'boolean',
        ]);

        if ($this->editingConceptoId) {
            Concepto::findOrFail($this->editingConceptoId)->update($this->conceptoForm);
            $message = 'Concepto actualizado.';
        } else {
            Concepto::create($this->conceptoForm);
            $message = 'Concepto creado.';
        }

        $this->editingConceptoId = null;
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function deleteConcepto(int $conceptoId): void
    {
        Concepto::findOrFail($conceptoId)->delete();
        $this->dispatch('notify', type: 'success', message: 'Concepto eliminado.');
    }

    public function closeConceptoModal(): void
    {
        $this->editingConceptoId = null;
        $this->conceptoForm = [
            'cliente_id' => null,
            'concepto' => '',
            'grupo' => '',
            'unidad' => 'UNID',
            'precio' => 0,
            'descuento' => 0,
            'impuesto' => 7,
            'retenciones' => 15,
            'unidadminimo' => 1,
            'observaciones' => '',
            'categoria' => '',
            'recurrente' => false,
        ];
    }

    public function loadContador(): void
    {
        $contador = ContadorFactura::query()
            ->where('ano', $this->ano)
            ->orderByDesc('id')
            ->first();

        $this->contador = $contador ? (int) $contador->contador : 0;
    }

    public function updatedAno(): void
    {
        $this->loadContador();
    }

    public function save(): void
    {
        $this->validate([
            'ano' => 'required|integer|min:2000',
            'contador' => 'required|integer|min:0',
        ]);

        ContadorFactura::query()->updateOrCreate(
            ['ano' => $this->ano],
            ['contador' => $this->contador]
        );

        $this->dispatch('notify', type: 'success', message: 'Contador de facturas actualizado.');
    }

    public function render()
    {
        $conceptos = $this->conceptos;
        $clientes = $this->clientes;

$categorias = Category::query()
            ->orderBy('name')
            ->get();
$users = User::query()
            ->orderBy('first_name')
            ->get();

        return view('livewire.facturacion.ajustes', compact('conceptos', 'clientes','categorias','users'))
            ->layout('layouts.front');
    }
}
