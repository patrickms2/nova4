<?php

namespace App\Livewire\Facturas;

use App\Models\Cliente;
use Livewire\Component;

class ClienteSelector extends Component
{
    public string $search = '';

    public ?int $selectedClienteId = null;

    public ?Cliente $selectedCliente = null;

    public bool $showResults = false;

    public int $limit = 10;

    // Modal "nuevo cliente"
    public bool $showCreateModal = false;

    public string $new_nombre = '';

    public string $new_dni = '';

    public string $new_email = '';

    public string $new_telefono = '';

    public string $new_domicilio = '';

    public string $new_poblacion = '';

    public string $new_codigopostal = '';

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
            $this->selectedClienteId = $cliente->id;
            $this->selectedCliente = $cliente;
            $this->search = $cliente->nombretotal ?? $cliente->nombre ?? '';
            $this->showResults = false;

            $this->dispatch('clienteSelected', clienteId: $this->selectedClienteId);
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

        $this->dispatch('clienteSelected', clienteId: null);
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
        $this->new_nombre = trim($this->search);
        $this->new_dni = '';
        $this->new_email = '';
        $this->new_telefono = '';
        $this->new_domicilio = '';
        $this->new_poblacion = '';
        $this->new_codigopostal = '';
    }

    public function saveNewCliente(): void
    {
        $data = $this->validate([
            'new_nombre' => 'required|string|min:2',
            'new_dni' => 'nullable|string|max:20',
            'new_email' => 'nullable|email|max:100',
            'new_telefono' => 'nullable|string|max:30',
            'new_domicilio' => 'nullable|string|max:255',
            'new_poblacion' => 'nullable|string|max:150',
            'new_codigopostal' => 'nullable|string|max:15',
        ], [], [
            'new_nombre' => 'nombre',
            'new_dni' => 'CIF/NIF',
        ]);

        $cliente = Cliente::create([
            'nombretotal' => $data['new_nombre'],
            'nombre' => $data['new_nombre'],
            'dni' => $data['new_dni'] ?: null,
            'email' => $data['new_email'] ?: null,
            'telefono' => $data['new_telefono'] ?: null,
            'domicilio' => $data['new_domicilio'] ?: null,
            'poblacion' => $data['new_poblacion'] ?: null,
            'codigopostal' => $data['new_codigopostal'] ?: null,
            'empresa_id' => 1,
        ]);

        $this->selectedClienteId = $cliente->id;
        $this->selectedCliente = $cliente;
        $this->search = $cliente->nombretotal;
        $this->showCreateModal = false;
        $this->showResults = false;

        $this->dispatch('clienteSelected', clienteId: $cliente->id);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Cliente creado y asignado a la factura.',
        ]);
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
