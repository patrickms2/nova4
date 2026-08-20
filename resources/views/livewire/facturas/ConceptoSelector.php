<?php

namespace App\Http\Livewire\Facturas;

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

        $this->emitUp(
            'conceptoSelectedForLinea',
            $this->lineIndex,
            $c->id,
            $c->precio,
            $c->unidad,
            $c->concepto
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

        $this->emitUp('conceptoSelectedForLinea', $this->lineIndex, null, null, null, null);
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
