<?php

namespace App\Livewire\Facturas;

use App\Models\Empresa;
use Livewire\Component;

class Empresas extends Component
{
    public string $search = '';

    public ?int $editingId = null;

    public array $form = [
        'empresa' => '',
        'nombre' => '',
        'logo_empresa' => '',
        'nif' => '',
        'email' => '',
        'telefono' => '',
        'direccion' => '',
        'poblacion' => '',
        'administrador' => '',
        'observaciones' => '',
    ];

    public array $recurrencia = [
        'empresa_id' => null,
        'dia' => 1,
        'activa' => true,
        'notas' => '',
    ];

    public function getEmpresasProperty()
    {
        return Empresa::query()
            ->when($this->search, fn ($q) => $q->where('empresa', 'like', "%{$this->search}%")
                ->orWhere('nif', 'like', "%{$this->search}%")
            )
            ->withCount('facturas','clientes')
            ->orderBy('empresa')
            ->get();
    }

    public function openEdit(int $id): void
    {
        $empresa = Empresa::findOrFail($id);
        $this->editingId = $id;
        $this->form = [
            'empresa' => $empresa->empresa ?? '',
            'nombre' => $empresa->nombre ?? '',
            'logo_empresa' => $empresa->logo_empresa ?? '',
            'nif' => $empresa->nif ?? '',
            'email' => $empresa->email ?? '',
            'telefono' => $empresa->telefono ?? '',
            'direccion' => $empresa->direccion ?? '',
            'poblacion' => $empresa->poblacion ?? '',
            'administrador' => $empresa->administrador ?? '',
            'observaciones' => $empresa->observaciones ?? '',
        ];
    }

    public function saveEmpresa(): void
    {
        $this->validate([
            'form.empresa' => 'required|string|max:255',
            'form.logo_empresa' => 'nullable|string|max:255',
            'form.nif' => 'nullable|string|max:20',
            'form.email' => 'nullable|email|max:255',
            'form.telefono' => 'nullable|string|max:20',
            'form.direccion' => 'nullable|string|max:255',
            'form.poblacion' => 'nullable|string|max:100',
            'form.administrador' => 'nullable|string|max:100',
            'form.observaciones' => 'nullable|string',
        ]);
        Empresa::findOrFail($this->editingId)->update($this->form);

        $this->editingId = null;
        $this->dispatch('notify', type: 'success', message: 'Empresa actualizada.');
    }

    public function render()
    {
        return view('livewire.facturacion.empresas')
            ->layout('layouts.front');
    }
}
