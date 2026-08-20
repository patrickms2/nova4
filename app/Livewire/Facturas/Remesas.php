<?php

namespace App\Livewire\Facturas;

use App\Models\Cliente;
use App\Models\Remesa;
use App\Services\Facturacion\RemesaGenerator;
use Illuminate\Support\Collection;
use Livewire\Component;

class Remesas extends Component
{
    public string $search = '';

    public bool $showCreateModal = false;

    public ?int $editingId = null;

    public array $form = [
        'nombre' => '',
        'fecha' => '',
        'notas' => '',
    ];

    /** @var array<int, bool> */
    public array $selectedClientes = [];

    public function mount(): void
    {
        $this->form['fecha'] = now()->format('Y-m-d');
    }

    public function getRemesasProperty(): Collection
    {
        return Remesa::query()
            ->withCount('remesaClientes')
            ->withCount('facturas')
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->orderByDesc('fecha')
            ->get();
    }

    public function getClientesProperty(): Collection
    {
        return Cliente::query()
            ->where('recurrencia_activa', true)
            ->orderBy('nombretotal')
            ->get(['id', 'nombretotal', 'recurrencia_dia']);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'selectedClientes']);
        $this->form = [
            'nombre' => 'Remesa '.now()->format('m/Y'),
            'fecha' => now()->format('Y-m-d'),
            'notas' => '',
        ];
        $this->showCreateModal = true;
    }

    public function openEdit(int $remesaId): void
    {
        $remesa = Remesa::with('remesaClientes')->findOrFail($remesaId);
        $this->editingId = $remesa->id;
        $this->form = [
            'nombre' => $remesa->nombre,
            'fecha' => $remesa->fecha->format('Y-m-d'),
            'notas' => $remesa->notas ?? '',
        ];
        $this->selectedClientes = [];
        foreach ($remesa->remesaClientes as $remesaCliente) {
            $this->selectedClientes[$remesaCliente->cliente_id] = true;
        }
        $this->showCreateModal = true;
    }

    public function closeModal(): void
    {
        $this->showCreateModal = false;
    }

    public function save(): void
    {
        $this->validate([
            'form.nombre' => 'required|string|max:150',
            'form.fecha' => 'required|date',
            'form.notas' => 'nullable|string',
            'selectedClientes' => 'required|array|min:1',
            'selectedClientes.*' => 'boolean',
        ]);

        $clienteIds = array_keys(array_filter($this->selectedClientes));

        if ($this->editingId) {
            $remesa = Remesa::findOrFail($this->editingId);
            $remesa->update([
                'nombre' => $this->form['nombre'],
                'fecha' => $this->form['fecha'],
                'notas' => $this->form['notas'] ?: null,
            ]);

            $existingIds = $remesa->remesaClientes()->pluck('cliente_id')->all();
            $toAdd = array_diff($clienteIds, $existingIds);
            $toRemove = array_diff($existingIds, $clienteIds);

            if (! empty($toRemove)) {
                $remesa->remesaClientes()->whereIn('cliente_id', $toRemove)->delete();
            }

            foreach ($toAdd as $clienteId) {
                $remesa->remesaClientes()->create(['cliente_id' => $clienteId]);
            }

            $this->closeModal();
            $this->dispatch('notify', type: 'success', message: 'Remesa actualizada correctamente.');
            return;
        }

        $remesa = Remesa::create([
            'nombre' => $this->form['nombre'],
            'fecha' => $this->form['fecha'],
            'notas' => $this->form['notas'] ?: null,
            'estado' => 'draft',
        ]);

        foreach ($clienteIds as $clienteId) {
            $remesa->remesaClientes()->create(['cliente_id' => $clienteId]);
        }

        $this->closeModal();
        $this->dispatch('notify', type: 'success', message: 'Remesa creada correctamente.');
    }

    public function generate(int $remesaId): void
    {
        $remesa = Remesa::findOrFail($remesaId);
        $result = app(RemesaGenerator::class)->generate($remesa);

        $message = "Generadas {$result['created']} factura(s).";
        if ($result['skipped'] > 0) {
            $message .= " Omitidos {$result['skipped']} cliente(s).";
        }

        $type = count($result['errors']) > 0 ? 'warning' : 'success';

        $this->dispatch('notify', type: $type, message: $message);
    }

    public function resetDraft(int $remesaId): void
    {
        $remesa = Remesa::findOrFail($remesaId);
        $remesa->update(['estado' => 'draft']);
        $this->dispatch('notify', type: 'success', message: 'Remesa marcada como borrador. Puedes volver a generarla.');
    }

    public function delete(int $remesaId): void
    {
        Remesa::findOrFail($remesaId)->delete();
        $this->dispatch('notify', type: 'success', message: 'Remesa eliminada.');
    }

    public function render()
    {
        return view('livewire.facturacion.remesas')
            ->layout('layouts.front');
    }
}
