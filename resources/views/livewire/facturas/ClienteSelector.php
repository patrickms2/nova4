<?php

namespace App\Http\Livewire\Facturas;

use App\Models\Cliente;
use Livewire\Component;

class ClienteSelector extends Component
{
    public string $search = '';

    public ?int $selectedClienteId = null;

    public ?Cliente $selectedCliente = null;

    public bool $showResults = false;

    public int $limit = 10;

    public function mount(?int $clienteId = null): void
    {
        if ($clienteId) {
            $this->setClienteById($clienteId);
        }
    }

    public function updatedSearch(): void
    {
        $this->showResults = strlen($this->search) > 1;
        $this->resetSelectedIfSearchChanged();
    }

    protected function resetSelectedIfSearchChanged(): void
    {
        if ($this->selectedCliente && $this->selectedCliente->nombretotal !== $this->search) {
            $this->selectedClienteId = null;
            $this->selectedCliente = null;
        }
    }

    public function setClienteById(int $id): void
    {
        $cliente = Cliente::find($id);

        if ($cliente) {
            $this->selectedClienteId = $cliente->codcliente ?? $cliente->id;
            $this->selectedCliente = $cliente;
            $this->search = $cliente->nombretotal ?? $cliente->nombre ?? '';
            $this->showResults = false;

            // Emitimos al padre (form de factura)
            $this->emitUp('clienteSelected', $this->selectedClienteId);
        }
    }

    public function selectCliente(int $id): void
    {
        $this->setClienteById($id);
    }

    public function clearCliente(): void
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->search = '';
        $this->showResults = false;

        $this->emitUp('clienteSelected', null);
    }

    public function getResultadosProperty()
    {
        if (strlen($this->search) < 2) {
            return collect();
        }

        $q = trim($this->search);

        return Cliente::query()
            ->where(function ($query) use ($q) {
                $query
                    ->where('nombretotal', 'LIKE', "%{$q}%")
                    ->orWhere('nombre', 'LIKE', "%{$q}%")
                    ->orWhere('dni', 'LIKE', "%{$q}%")
                    ->orWhere('email', 'LIKE', "%{$q}%")
                    ->orWhere('telefono', 'LIKE', "%{$q}%");
            })
            ->orderBy('nombretotal')
            ->limit($this->limit)
            ->get();
    }

    public function render()
    {
        return view('livewire.facturas.cliente-selector');
    }
}
