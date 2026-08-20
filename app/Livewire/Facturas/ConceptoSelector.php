<?php

namespace App\Livewire\Facturas;

use App\Models\Concepto;
use Livewire\Component;

class ConceptoSelector extends Component
{
    public string $search = '';

    public ?int $selectedConceptoId = null;

    public ?Concepto $selectedConcepto = null;

    public bool $showResults = false;

    public int $limit = 10;

    public int $lineIndex = 0;

    // Modal nuevo concepto
    public bool $showCreateModal = false;

    public string $new_concepto = '';

    public string $new_grupo = '';

    public string $new_unidad = 'ud';

    public ?float $new_precio = 0.0;

    public ?float $new_impuesto = 7.0;

    public ?float $new_retencion = 15.0;

    public function mount(int $lineIndex = 0, ?int $conceptoId = null): void
    {
        $this->lineIndex = $lineIndex;

        if ($conceptoId) {
            $this->setConceptoById($conceptoId);
        }
    }

    public function updatedSearch(): void
    {
        $this->showResults = strlen($this->search) > 1;
        $this->resetIfChanged();
    }

    protected function resetIfChanged(): void
    {
        if ($this->selectedConcepto && $this->selectedConcepto->concepto !== $this->search) {
            $this->selectedConceptoId = null;
            $this->selectedConcepto = null;
        }
    }

    public function setConceptoById(int $id): void
    {
        $c = Concepto::find($id);
        if (! $c) {
            return;
        }

        $this->selectedConceptoId = $c->id;
        $this->selectedConcepto = $c;
        $this->search = $c->concepto;
        $this->showResults = false;

        $this->dispatch(
            'conceptoSelectedForLinea',
            lineIndex: $this->lineIndex,
            conceptoId: $c->id,
            precio: $c->precio,
            unidad: $c->unidad,
            descripcion: $c->concepto,
            impuesto: $c->impuesto,
            retencion: $c->retenciones,
        );
    }

    public function selectConcepto(int $id): void
    {
        $this->setConceptoById($id);
    }

    public function clearConcepto(): void
    {
        $this->selectedConceptoId = null;
        $this->selectedConcepto = null;
        $this->search = '';
        $this->showResults = false;

        $this->dispatch('conceptoSelectedForLinea', lineIndex: $this->lineIndex, conceptoId: null, precio: null, unidad: null, descripcion: null, impuesto: null, retencion: null);
    }

    public function openCreateModal(): void
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    protected function resetCreateForm(): void
    {
        $this->new_concepto = trim($this->search);
        $this->new_grupo = '';
        $this->new_unidad = 'ud';
        $this->new_precio = 0.0;
        $this->new_impuesto = 7.0;
        $this->new_retencion = 15.0;
    }

    public function saveNewConcepto(): void
    {
        $data = $this->validate([
            'new_concepto' => 'required|string|min:2',
            'new_grupo' => 'nullable|string|max:50',
            'new_unidad' => 'nullable|string|max:20',
            'new_precio' => 'required|numeric|min:0',
            'new_impuesto' => 'nullable|numeric|min:0',
            'new_retencion' => 'nullable|numeric|min:0',
        ], [], [
            'new_concepto' => 'concepto',
            'new_precio' => 'precio',
        ]);

        $concepto = Concepto::create([
            'concepto' => $data['new_concepto'],
            'grupo' => $data['new_grupo'] ?: null,
            'unidad' => $data['new_unidad'] ?: 'ud',
            'precio' => $data['new_precio'],
            'impuesto' => $data['new_impuesto'] ?? 7,
            'retenciones' => $data['new_retencion'] ?? 15,
            'codempresa' => 1,
        ]);

        $this->selectedConceptoId = $concepto->id;
        $this->selectedConcepto = $concepto;
        $this->search = $concepto->concepto;
        $this->showCreateModal = false;
        $this->showResults = false;

        $this->dispatch(
            'conceptoSelectedForLinea',
            lineIndex: $this->lineIndex,
            conceptoId: $concepto->id,
            precio: $concepto->precio,
            unidad: $concepto->unidad,
            descripcion: $concepto->concepto,
            impuesto: $concepto->impuesto,
            retencion: $concepto->retenciones,
        );

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Concepto creado y aplicado a la línea.',
        ]);
    }

    public function getResultadosProperty()
    {
        if (strlen($this->search) < 2) {
            return collect();
        }

        $q = trim($this->search);

        return Concepto::query()
            ->where(function ($query) use ($q) {
                $query
                    ->where('concepto', 'LIKE', "%{$q}%")
                    ->orWhere('codigo', 'LIKE', "%{$q}%")
                    ->orWhere('grupo', 'LIKE', "%{$q}%");
            })
            ->orderBy('concepto')
            ->limit($this->limit)
            ->get();
    }

    public function render()
    {
        return view('livewire.facturas.concepto-selector');
    }
}
