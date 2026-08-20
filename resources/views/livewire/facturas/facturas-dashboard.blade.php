<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Factura;
use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Empresa;
use Illuminate\Support\Carbon;

new class extends Component
{
    use WithPagination;

    public ?int $facturaId = null;
    public ?int $cliente_id = null;
    public ?int $selectedConceptoId = null;
    public ?Concepto $selectedConcepto = null;
    public int $empresa_id = 1;
    public int $limit = 10;
    public int $lineIndex = 0;
    public string $numero = '';
    public string $serie = 'A';
    public ?string $fecha = null;
    public ?string $notas = null;

    public array $lineas = [];

    public float $subtotal = 0;
    public float $total_igic = 0;
    public float $total_retencion = 0;
    public float $total_factura = 0;

    public string $search = '';
    public ?int $empresaFilter = null;
    public ?string $statusFilter = null;

    public bool $showEditor = false;
    public ?int $editingId = null;

    public array $form = [
        'empresa_id'     => null,
        'cliente_id'     => null,
        'fechaemitido'   => '',
        'notas'          => '',
        'lineas'         => [],
        'baseimponible'  => 0,
        'baseexenta'     => 0,
        'impuesto'       => 0,
        'retenciones'    => 0,
        'importe'        => 0,
    ];

    // Autocomplete cliente
    public string $clienteSearch = '';
    public array $clienteSuggestions = [];
    public array $facturas = [];
    public array $facturas_registros = [];
    public array $clientes = [];
    public array $conceptos = [];
    public array $factura = [];

    // Autocomplete conceptos (para una línea concreta)
    public string $conceptoSearch = '';
    public array $conceptoSuggestions = [];
    public ?int $conceptoLineaIndex = null;

    // Modales
    public bool $showClienteModal = false;
    public bool $showConceptoModal = false;

    // Formularios modales
    public array $nuevoCliente = [
        'nombretotal' => '',
        'dni'         => '',
        'email'       => '',
        'telefono'    => '',
        'domicilio'   => '',
        'poblacion'   => '',
    ];

    public array $nuevoConcepto = [
        'concepto'    => '',
        'grupo'       => '',
        'unidad'      => 'UNID',
        'precio'      => 0,
        'descuento'   => 0,
        'impuesto'    => 7,
        'retenciones' => 15,
    ];

    protected $rules = [
        'form.empresa_id'               => 'required|exists:empresas,id',
        'form.cliente_id'               => 'required|exists:clientes,id',
        'form.fechaemitido'             => 'required|date',
        'form.lineas'                   => 'required|array|min:1',
        'form.lineas.*.descripcion'     => 'required|string',
        'form.lineas.*.cantidad'        => 'required|numeric|min:0.01',
        'form.lineas.*.precio'          => 'required|numeric|min:0',
        'form.lineas.*.descuento'       => 'nullable|numeric|min:0',
        'form.lineas.*.impuesto'        => 'nullable|numeric|min:0',
        'form.lineas.*.retenciones'     => 'nullable|numeric|min:0',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }



    public function mount(?int $facturaId = null): void
    {

        $this->lineIndex = $lineIndex;

        if ($conceptoId) {
            $this->setConceptoById($conceptoId);
        }

        $this->facturaId = $facturaId;

        $this->fecha = now()->format('Y-m-d');

        if ($facturaId) {
            $this->factura = Factura::with('registros')->findOrFail($facturaId);
        } else {
            $this->lineas = [
                $this->nuevaLinea(),
            ];
            $this->recalcularTotales();
        }

        // Fecha por defecto: hoy
        $this->form['fechaemitido'] = now()->format('Y-m-d');
        $this->facturas = $this->getFacturasProperty();
        $this->clientes = Cliente::all();
        $this->conceptos = $this->getConceptosProperty();
    }

    public function getConceptosProperty()
    {

        return Concepto::query()
            ->when($this->search, function ($q) {
                $q->where('concepto', 'LIKE', "%{$this->search}%")
                    ->orWhere('codigo', 'LIKE', "%{$this->search}%")
                    ->orWhere('grupo', 'LIKE', "%{$this->search}%");
            })
            ->orderBy('concepto')
            ->limit($this->limit)
            ->get();
    }
    public function getClientesProperty()
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


    public function getFacturasProperty()
    {
        return Factura::query()
            ->when($this->search, function ($q) {
                $q->where('codfactura', 'like', "%{$this->search}%")
                    ->orWhereHas('cliente', fn ($qq) =>
                    $qq->where('nombretotal', 'like', "%{$this->search}%")
                        ->orWhere('dni', 'like', "%{$this->search}%")
                    );
            })
            ->when($this->empresaFilter, fn ($q) => $q->where('empresa_id', $this->empresaFilter))
            ->latest('fechaemitido')
            ->paginate(10);
    }

    public function getEmpresasProperty()
    {
        return Empresa::orderBy('empresa')->get();
    }



    public function newFactura(): void
    {
        $this->reset(['editingId']);
        $this->form = [
            'empresa_id'     => $this->empresaFilter,
            'cliente_id'     => null,
            'fechaemitido'   => now()->format('Y-m-d'),
            'notas'          => '',
            'lineas'         => [
                [
                    'concepto_id'     => null,
                    'descripcion'     => '',
                    'cantidad'        => 1,
                    'unidad'          => 'UNID',
                    'precio'          => 0,
                    'descuento'       => 0,
                    'impuesto'        => 7,   // IGIC por defecto
                    'retenciones'     => 15,  // Retención por defecto
                    'valorimpuesto'   => 0,
                    'valorretenciones'=> 0,
                    'importe'         => 0,
                ],
            ],
            'baseimponible'  => 0,
            'baseexenta'     => 0,
            'impuesto'       => 0,
            'retenciones'    => 0,
            'importe'        => 0,
        ];

        $this->recalcularTotales();
        $this->showEditor = true;
    }

    public function editFactura(int $id): void
    {
        $factura = Factura::with('registros')->findOrFail($id);
        $this->editingId = $factura->id;

        $this->form['empresa_id']    = $factura->empresa_id;
        $this->form['cliente_id']    = $factura->cliente_id;
        $this->form['fechaemitido']  = optional($factura->fechaemitido)->format('Y-m-d');
        $this->form['notas']         = $factura->notas ?? '';

        $this->form['lineas'] = $factura->registros->map(function ($reg) {
            return [
                'concepto_id'      => $reg->codconcepto,
                'descripcion'      => $reg->descripcion,
                'cantidad'         => (float) $reg->cantidad,
                'unidad'           => $reg->unidad ?? 'UNID',
                'precio'           => (float) $reg->precio,
                'descuento'        => (float) ($reg->descuento ?? 0),
                'impuesto'         => (float) ($reg->impuesto ?? 7),
                'retenciones'      => (float) ($reg->retenciones ?? 15),
                'valorimpuesto'    => (float) ($reg->valorimpuesto ?? 0),
                'valorretenciones' => (float) ($reg->valorretenciones ?? 0),
                'importe'          => (float) $reg->importe,
            ];
        })->toArray();

        $this->form['baseimponible'] = (float) $factura->baseimponible;
        $this->form['baseexenta']    = (float) ($factura->baseexenta ?? 0);
        $this->form['impuesto']      = (float) $factura->impuesto;
        $this->form['retenciones']   = (float) $factura->retenciones;
        $this->form['importe']       = (float) $factura->importe;

        $this->showEditor = true;
    }

    public function addLinea(): void
    {
        $this->form['lineas'][] = [
            'concepto_id'      => null,
            'descripcion'      => '',
            'cantidad'         => 1,
            'unidad'           => 'UNID',
            'precio'           => 0,
            'descuento'        => 0,
            'impuesto'         => 7,
            'retenciones'      => 15,
            'valorimpuesto'    => 0,
            'valorretenciones' => 0,
            'importe'          => 0,
        ];

        $this->recalcularTotales();
    }

    public function removeLinea(int $index): void
    {
        unset($this->form['lineas'][$index]);
        $this->form['lineas'] = array_values($this->form['lineas']);
        $this->recalcularTotales();
    }

    public function updatedForm(): void
    {
        $this->recalcularTotales();
    }

    protected function recalcularTotales(): void
    {
        $base = 0;
        $igic = 0;
        $ret = 0;

        foreach ($this->form['lineas'] as $i => &$linea) {
            $cantidad  = (float) ($linea['cantidad'] ?? 0);
            $precio    = (float) ($linea['precio'] ?? 0);
            $dto       = (float) ($linea['descuento'] ?? 0);
            $tipoI     = (float) ($linea['impuesto'] ?? 7);
            $tipoR     = (float) ($linea['retenciones'] ?? 15);

            $bruto   = $cantidad * $precio;
            $importeDto = $bruto * ($dto / 100);
            $baseLinea  = $bruto - $importeDto;

            $valorI = $baseLinea * ($tipoI / 100);
            $valorR = $baseLinea * ($tipoR / 100);

            $linea['valorimpuesto']    = round($valorI, 2);
            $linea['valorretenciones'] = round($valorR, 2);
            $linea['importe']          = round($baseLinea, 2);

            $base += $baseLinea;
            $igic += $valorI;
            $ret  += $valorR;
        }

        $this->form['baseimponible'] = round($base, 2);
        $this->form['baseexenta']    = round((float) ($this->form['baseexenta'] ?? 0), 2);
        $this->form['impuesto']      = round($igic, 2);
        $this->form['retenciones']   = round($ret, 2);
        $this->form['importe']       = round($base + $igic - $ret, 2);
    }

    // Autocomplete cliente
    public function updatedClienteSearch(): void
    {
        $q = trim($this->clienteSearch);

        if ($q === '') {
            $this->clienteSuggestions = [];
            return;
        }

        $this->clienteSuggestions = Cliente::query()
            ->where('nombretotal', 'like', "%{$q}%")
            ->orWhere('dni', 'like', "%{$q}%")
            ->limit(8)
            ->get(['id', 'nombretotal', 'dni'])
            ->map(fn ($c) => [
                'id'    => $c->id,
                'label' => $c->nombretotal . ($c->dni ? " ({$c->dni})" : ''),
            ])
            ->toArray();
    }

    public function selectCliente(int $id): void
    {
        $this->form['cliente_id'] = $id;
        $cliente = Cliente::find($id);
        $this->clienteSearch = $cliente ? $cliente->nombretotal : '';
        $this->clienteSuggestions = [];
    }

    // Autocomplete concepto
    public function openConceptoSearch(int $lineIndex): void
    {
        $this->conceptoLineaIndex = $lineIndex;
        $this->conceptoSearch = '';
        $this->conceptoSuggestions = [];
    }

    public function updatedConceptoSearch(): void
    {
        $q = trim($this->conceptoSearch);
        if ($q === '') {
            $this->conceptoSuggestions = [];
            return;
        }

        $this->conceptoSuggestions = Concepto::query()
            ->where('concepto', 'like', "%{$q}%")
            ->orWhere('codigo', 'like', "%{$q}%")
            ->limit(10)
            ->get(['id', 'concepto', 'precio', 'descuento', 'impuesto', 'retenciones', 'unidad'])
            ->map(fn ($c) => [
                'id'         => $c->id,
                'label'      => $c->concepto,
                'precio'     => (float) ($c->precio ?? 0),
                'descuento'  => (float) ($c->descuento ?? 0),
                'impuesto'   => (float) ($c->impuesto ?? 7),
                'retenciones'=> (float) ($c->retenciones ?? 15),
                'unidad'     => $c->unidad ?? 'UNID',
            ])
            ->toArray();
    }

    public function selectConcepto(int $conceptoId): void
    {
        if ($this->conceptoLineaIndex === null) {
            return;
        }

        $lineIndex = $this->conceptoLineaIndex;
        $concepto = Concepto::findOrFail($conceptoId);

        $this->form['lineas'][$lineIndex]['concepto_id'] = $concepto->id;
        $this->form['lineas'][$lineIndex]['descripcion'] = $concepto->concepto;
        $this->form['lineas'][$lineIndex]['precio']      = (float) ($concepto->precio ?? 0);
        $this->form['lineas'][$lineIndex]['descuento']   = (float) ($concepto->descuento ?? 0);
        $this->form['lineas'][$lineIndex]['impuesto']    = (float) ($concepto->impuesto ?? 7);
        $this->form['lineas'][$lineIndex]['retenciones'] = (float) ($concepto->retenciones ?? 15);
        $this->form['lineas'][$lineIndex]['unidad']      = $concepto->unidad ?? 'UNID';

        $this->conceptoSuggestions = [];
        $this->conceptoSearch = '';
        $this->conceptoLineaIndex = null;

        $this->recalcularTotales();
    }

    // Modales nuevo cliente / concepto
    public function openClienteModal(): void
    {
        $this->nuevoCliente = [
            'nombretotal' => $this->clienteSearch,
            'dni'         => '',
            'email'       => '',
            'telefono'    => '',
            'domicilio'   => '',
            'poblacion'   => '',
        ];
        $this->showClienteModal = true;
    }

    public function saveNuevoCliente(): void
    {
        $cliente = Cliente::create($this->nuevoCliente);
        $this->showClienteModal = false;
        $this->selectCliente($cliente->id);
    }

    public function openConceptoModal(int $lineIndex): void
    {
        $this->conceptoLineaIndex = $lineIndex;
        $this->nuevoConcepto = [
            'concepto'    => '',
            'grupo'       => '',
            'unidad'      => 'UNID',
            'precio'      => 0,
            'descuento'   => 0,
            'impuesto'    => 7,
            'retenciones' => 15,
        ];
        $this->showConceptoModal = true;
    }

    public function saveNuevoConcepto(): void
    {
        $concepto = Concepto::create($this->nuevoConcepto);
        $this->showConceptoModal = false;
        $this->selectConcepto($concepto->id);
    }

    public function save(): void
    {
        $this->validate();
        $this->recalcularTotales();

        $data = $this->form;
        $factura = $this->editingId
            ? Factura::findOrFail($this->editingId)
            : new Factura();

        $factura->empresa_id    = $data['empresa_id'];
        $factura->cliente_id    = $data['cliente_id'];
        $factura->fechaemitido  = Carbon::parse($data['fechaemitido']);
        $factura->baseimponible = $data['baseimponible'];
        $factura->baseexenta    = $data['baseexenta'];
        $factura->impuesto      = $data['impuesto'];
        $factura->retenciones   = $data['retenciones'];
        $factura->importe       = $data['importe'];
        $factura->notas         = $data['notas'] ?? null;
        $factura->save();

        // Regenerar líneas
        $factura->registros()->delete();
        foreach ($data['lineas'] as $linea) {
            $factura->registros()->create([
                'codconcepto'      => $linea['concepto_id'],
                'descripcion'      => $linea['descripcion'],
                'cantidad'         => $linea['cantidad'],
                'unidad'           => $linea['unidad'],
                'precio'           => $linea['precio'],
                'descuento'        => $linea['descuento'],
                'impuesto'         => $linea['impuesto'],
                'retenciones'      => $linea['retenciones'],
                'valorimpuesto'    => $linea['valorimpuesto'],
                'valorretenciones' => $linea['valorretenciones'],
                'importe'          => $linea['importe'],
                'fecha'            => $data['fechaemitido'],
            ]);
        }

        $this->showEditor = false;
        $this->resetPage();
        $this->dispatch('notify', type: 'success', message: 'Factura guardada correctamente.');
    }
};

?>
@extends('layouts.standalone')

@section('content')

<div class="space-y-6">
    {{-- Header tipo invoice-list --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-default-900">Facturas</h1>
            <p class="text-sm text-default-500">Listado de facturas con búsqueda, filtros y editor en vivo.</p>
        </div>
        <div class="flex items-center gap-3">
            <input
                type="text"
                wire:model.debounce.300ms="search"
                placeholder="Buscar por nº o cliente…"
                class="h-9 w-56 rounded-md border border-default-300 bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-primary"
            >
            <select
                wire:model="empresaFilter"
                class="h-9 rounded-md border border-default-300 bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-primary"
            >
                <option value="">Todas las empresas</option>
                @foreach($this->empresas as $empresa)
                    <option value="{{ $empresa->id }}">{{ $empresa->empresa }}</option>
                @endforeach
            </select>

            <button
                type="button"
                wire:click="newFactura"
                class="inline-flex items-center gap-1 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/80"
            >
                <span class="icon-[heroicons--plus-small] w-4 h-4"></span>
                Nueva factura
            </button>
        </div>
    </div>


    <div class="py-8 max-w-7xl mx-auto">

        <h1 class="text-2xl font-bold mb-4">Gestión de Citas Taxi</h1>
        @if(session('message'))
            <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded">
                {{ session('message') }}
            </div>
        @endif

        <div class="bg-white p-6 rounded shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <flux:select wire:model.live="filters.status" label="Estado" variant="listbox" placeholder="Estado..." >
                    <flux:select.option value="" wire:key="">Todos</flux:select.option>
                    @foreach($statuses as $value => $label)
                        <flux:select.option value="{{ $value }}" wire:key="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="filters.type"  label="Tipo"  variant="listbox" placeholder="Estado..." >
                    <flux:select.option value="" wire:key="">Todos</flux:select.option>
                    @foreach($statuses as $value => $label)
                        <flux:select.option value="{{ $value }}" wire:key="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="date" wire:model="filters.date_start" label="F. Inicio" />
                <flux:input type="date" wire:model="filters.date_end" label="F. Fin" />

                <div class="md:col-span-4 text-right">
                    <flux:button  wire:click="openCreateModal" variant="primary" icon="plus"  color="green">Nueva Cita</flux:button>
                    <flux:button  wire:click="buscar" icon="magnifying-glass" variant="primary" color="zinc">Buscar</flux:button>
                    <flux:button  wire:click="resetFilters" variant="danger" color="red">Limpiar</flux:button>
                </div>
            </div>
        </div>
        <div class="bg-white p-6 rounded shadow mb-6">

            <flux:table>
                <flux:table.columns>
                    <flux:table.column></flux:table.column>
                    <flux:table.column  sortable sorted direction="desc"  wire:click="sort('id')" class="max-md:hidden">ID</flux:table.column>
                    <flux:table.column  sortable sorted direction="desc"  wire:click="sort('appointment_date')" class="max-md:hidden">Fecha</flux:table.column>
                    <flux:table.column sortable sorted direction="desc"  wire:click="sort('departamento.nombre')"><span class="max-md:hidden">Cliente</span><div class="md:hidden w-6">Departamento</div></flux:table.column>
                    <flux:table.column sortable sorted direction="desc"  wire:click="sort('usuario.nombre')"><span class="max-md:hidden">Base</span><div class="md:hidden w-6">Usuario</div></flux:table.column>
                    <flux:table.column sortable sorted direction="desc"  wire:click="sort('status')">Total</flux:table.column>
                    <flux:table.column sortable sorted direction="desc"  wire:click="sort('appointment_type')">Estado</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($facturas as $factura)
                        @php
                            //print_r($row);
                        @endphp
                        <flux:table.row>
                            <flux:table.cell><flux:checkbox /></flux:table.cell>
                            <flux:table.cell class="max-md:hidden">#{{ $factura['id'] }}</flux:table.cell>
                            <flux:table.cell>{{ $factura->codfactura ?? $factura->id }}</flux:table.cell>
                            <flux:table.cell class="max-w-6 truncate">
                                <div class="text-sm text-gray-900 dark:text-white font-medium">
                                    {{ optional($factura->fechaemitido)->format('d/m/Y') }}
                                </div>
                                <flux:badge color="red" size="sm" inset="top bottom">{{ optional($factura->fechaemitido)->format('d/m/Y') }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="min-w-6">
                                <div class="flex items-center gap-2">
                                    <flux:avatar src="https://i.pravatar.cc/48?img={{ $loop->index }}" size="xs" />
                                    <span class="max-md:hidden">{{  optional($factura->cliente)->nombretotal ?? '—' }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="max-md:hidden"><flux:badge :color="$row['status']" size="sm" inset="top bottom">{{ $row['status'] }}</flux:badge></flux:table.cell>
                            <flux:table.cell class="text-sm text-right" variant="strong">{{ number_format($factura->baseimponible, 2, ',', '.') }} €</flux:table.cell>
                            <flux:table.cell class="text-sm text-right font-semibold" variant="strong"> {{ number_format($factura->importe, 2, ',', '.') }} €</flux:table.cell>
                            <flux:table.cell>
                                <button wire:click="editFactura({{ $factura->id }})"
                                        class="inline-flex cursor-pointer items-center px-3 py-1.5 border border-red-300 dark:border-red-700 rounded-md text-sm font-medium text-red-700 dark:text-red-300 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors focus:outline-none">
                                    Editar
                                </button>

                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

    </div>


    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1.1fr)] gap-6">
        {{-- LISTADO --}}
        <div class="rounded-md bg-card shadow-sm">
            <div class="border-b border-default-200 px-4 py-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-default-800">Listado</h2>
                {{-- aquí podrías poner filtros rápidos, tags, etc. --}}
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-default-200 text-xs text-default-500">
                    <tr>
                        <th class="py-2 text-left">Nº</th>
                        <th class="py-2 text-left">Fecha</th>
                        <th class="py-2 text-left">Cliente</th>
                        <th class="py-2 text-right">Base</th>
                        <th class="py-2 text-right">Total</th>
                        <th class="py-2 text-right">Acciones</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-default-100">
                    @forelse($this->facturas as $factura)
                        <tr class="hover:bg-default-50/40">
                            <td class="py-2 text-xs font-mono text-default-700">
                                {{ $factura->codfactura ?? $factura->id }}
                            </td>
                            <td class="py-2 text-sm text-default-700">
                                {{ optional($factura->fechaemitido)->format('d/m/Y') }}
                            </td>
                            <td class="py-2 text-sm">
                                {{ optional($factura->cliente)->nombretotal ?? '—' }}
                            </td>
                            <td class="py-2 text-sm text-right">
                                {{ number_format($factura->baseimponible, 2, ',', '.') }} €
                            </td>
                            <td class="py-2 text-sm text-right font-semibold">
                                {{ number_format($factura->importe, 2, ',', '.') }} €
                            </td>
                            <td class="py-2 text-sm text-right">
                                <button
                                    type="button"
                                    wire:click="editFactura({{ $factura->id }})"
                                    class="inline-flex items-center rounded-md border border-default-300 px-2 py-1 text-xs hover:border-primary hover:text-primary"
                                >
                                    Editar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-sm text-default-500">
                                No hay facturas que coincidan con la búsqueda.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $this->facturas->links() }}
                </div>
            </div>
        </div>

        {{-- EDITOR SLIDE-IN --}}
        <div
            x-data="{ open: @entangle('showEditor') }"
            x-cloak
            x-show="open"
            x-transition
            class="rounded-md bg-card shadow-lg border border-default-200 flex flex-col"
        >
            <div class="border-b border-default-200 px-4 py-3 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-default-900">
                        @if($editingId) Editar factura @else Nueva factura @endif
                    </h2>
                    <p class="text-xs text-default-500">Empresa, cliente, líneas y totales en la misma vista.</p>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="text-default-400 hover:text-default-700"
                >
                    ✕
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                {{-- Empresa + Fecha --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-default-600">Empresa</label>
                        <select
                            wire:model="form.empresa_id"
                            class="w-full rounded-md border border-default-300 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Selecciona empresa…</option>
                            @foreach($this->empresas as $empresa)
                                <option value="{{ $empresa->id }}">{{ $empresa->empresa }}</option>
                            @endforeach
                        </select>
                        @error('form.empresa_id') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-medium text-default-600">Fecha</label>
                        <input
                            type="date"
                            wire:model="form.fechaemitido"
                            class="w-full rounded-md border border-default-300 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                        @error('form.fechaemitido') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Cliente: autocomplete + botón nuevo --}}
                <div class="space-y-1" x-data="{ openDrop: false }">
                    <label class="text-xs font-medium text-default-600">Cliente</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input
                                type="text"
                                x-on:focus="openDrop = true"
                                x-on:click.outside="openDrop = false"
                                wire:model.debounce.300ms="clienteSearch"
                                placeholder="Buscar cliente por nombre o NIF…"
                                class="w-full rounded-md border border-default-300 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                            @if(!empty($clienteSuggestions))
                                <div
                                    x-show="openDrop"
                                    class="absolute mt-1 w-full rounded-md border border-default-200 bg-card shadow-lg z-20"
                                >
                                    @foreach($clienteSuggestions as $s)
                                        <button
                                            type="button"
                                            wire:click="selectCliente({{ $s['id'] }})"
                                            class="block w-full px-3 py-1.5 text-left text-xs hover:bg-default-50"
                                        >
                                            {{ $s['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <button
                            type="button"
                            wire:click="openClienteModal"
                            class="inline-flex items-center rounded-md border border-default-300 px-3 text-xs hover:border-primary hover:text-primary"
                        >
                            + Nuevo
                        </button>
                    </div>
                    @error('form.cliente_id') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Líneas --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-semibold text-default-700">Líneas</h3>
                        <button
                            type="button"
                            wire:click="addLinea"
                            class="inline-flex items-center rounded-md border border-dashed border-default-300 px-2 py-1 text-xs hover:border-primary hover:text-primary"
                        >
                            + Añadir línea
                        </button>
                    </div>

                    <div class="space-y-2">
                        @foreach($form['lineas'] as $index => $linea)
                            <div class="rounded-md border border-default-200 bg-default-50/40 p-3 space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    {{-- Concepto search --}}
                                    <div class="flex-1 space-y-1" x-data="{ openDrop: false }">
                                        <label class="text-[11px] font-medium text-default-600">Concepto / descripción</label>
                                        <div class="flex gap-2">
                                            <div class="relative flex-1">
                                                <input
                                                    type="text"
                                                    wire:model.defer="form.lineas.{{ $index }}.descripcion"
                                                    placeholder="Descripción…"
                                                    class="w-full rounded-md border border-default-300 bg-background px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-primary"
                                                >
                                            </div>
                                            <button
                                                type="button"
                                                x-on:click="openDrop = true"
                                                wire:click="openConceptoSearch({{ $index }})"
                                                class="inline-flex items-center rounded-md border border-default-300 px-2 text-[11px] hover:border-primary hover:text-primary"
                                            >
                                                Buscar
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="openConceptoModal({{ $index }})"
                                                class="inline-flex items-center rounded-md border border-default-300 px-2 text-[11px] hover:border-primary hover:text-primary"
                                            >
                                                + Nuevo
                                            </button>
                                        </div>

                                        {{-- dropdown conceptos global, pero UX rápida --}}
                                        @if($conceptoLineaIndex === $index && !empty($conceptoSuggestions))
                                            <div
                                                class="mt-1 max-h-40 overflow-y-auto rounded-md border border-default-200 bg-card text-xs shadow-lg z-30"
                                            >
                                                @foreach($conceptoSuggestions as $opt)
                                                    <button
                                                        type="button"
                                                        wire:click="selectConcepto({{ $opt['id'] }})"
                                                        class="block w-full px-3 py-1 text-left hover:bg-default-50"
                                                    >
                                                        {{ $opt['label'] }}
                                                        <span class="text-[10px] text-default-500">
                                                            · {{ number_format($opt['precio'], 2, ',', '.') }} €
                                                        </span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="removeLinea({{ $index }})"
                                        class="text-[11px] text-red-500 hover:underline"
                                    >
                                        Eliminar
                                    </button>
                                </div>

                                {{-- Cantidad / precio / dto --}}
                                <div class="grid grid-cols-4 gap-2 text-[11px]">
                                    <div>
                                        <label class="block text-[10px] text-default-600">Cantidad</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.cantidad"
                                            class="w-full rounded-md border border-default-300 bg-background px-2 py-1 text-[11px]"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-default-600">Precio</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.precio"
                                            class="w-full rounded-md border border-default-300 bg-background px-2 py-1 text-[11px]"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-default-600">Dto. %</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.descuento"
                                            class="w-full rounded-md border border-default-300 bg-background px-2 py-1 text-[11px]"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-default-600">Unidad</label>
                                        <input
                                            type="text"
                                            wire:model.lazy="form.lineas.{{ $index }}.unidad"
                                            class="w-full rounded-md border border-default-300 bg-background px-2 py-1 text-[11px]"
                                        >
                                    </div>
                                </div>

                                {{-- Impuestos / importe --}}
                                <div class="grid grid-cols-4 gap-2 text-[11px]">
                                    <div>
                                        <label class="block text-[10px] text-default-600">IGIC %</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.impuesto"
                                            class="w-full rounded-md border border-default-300 bg-background px-2 py-1 text-[11px]"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-default-600">Ret. %</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.retenciones"
                                            class="w-full rounded-md border border-default-300 bg-background px-2 py-1 text-[11px]"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-default-600">IGIC €</label>
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ number_format($linea['valorimpuesto'] ?? 0, 2, ',', '.') }}"
                                            class="w-full rounded-md border border-default-200 bg-default-50 px-2 py-1 text-[11px]"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-default-600">Importe</label>
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ number_format($linea['importe'] ?? 0, 2, ',', '.') }}"
                                            class="w-full rounded-md border border-default-200 bg-default-50 px-2 py-1 text-[11px] font-semibold"
                                        >
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Totales --}}
                <div class="border-t border-default-200 pt-3">
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-default-600">Base imponible</span>
                            <span class="font-semibold">
                                {{ number_format($form['baseimponible'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-default-600">Base exenta</span>
                            <span class="font-semibold">
                                {{ number_format($form['baseexenta'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-default-600">IGIC 7%</span>
                            <span class="font-semibold">
                                {{ number_format($form['impuesto'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-default-600">Retenciones 15%</span>
                            <span class="font-semibold">
                                {{ number_format($form['retenciones'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="font-semibold text-default-900">Total factura</span>
                            <span class="font-semibold text-default-900">
                                {{ number_format($form['importe'], 2, ',', '.') }} €
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer botones --}}
            <div class="border-t border-default-200 px-4 py-3 flex items-center justify-between">
                <button
                    type="button"
                    wire:click="$set('showEditor', false)"
                    class="text-xs text-default-500 hover:text-default-800"
                >
                    Cancelar
                </button>
                <div class="flex items-center gap-3">
                    {{-- aquí podríamos poner “PDF”, “Duplicar”, etc. --}}
                    <button
                        type="button"
                        wire:click="save"
                        class="inline-flex items-center rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/80"
                    >
                        Guardar factura
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL NUEVO CLIENTE --}}
    <div
        x-data
        x-cloak
        x-show="@js($showClienteModal)"
        class="fixed inset-0 z-40 flex items-center justify-center bg-black/40"
    >
        <div class="w-full max-w-md rounded-md bg-card p-4 shadow-lg">
            <h2 class="text-sm font-semibold mb-2">Nuevo cliente</h2>
            <div class="space-y-2 text-xs">
                <input type="text" wire:model="nuevoCliente.nombretotal" placeholder="Nombre" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                <input type="text" wire:model="nuevoCliente.dni" placeholder="DNI / NIF" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                <input type="email" wire:model="nuevoCliente.email" placeholder="Email" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                <input type="text" wire:model="nuevoCliente.telefono" placeholder="Teléfono" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                <input type="text" wire:model="nuevoCliente.domicilio" placeholder="Domicilio" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                <input type="text" wire:model="nuevoCliente.poblacion" placeholder="Población" class="w-full rounded-md border border-default-300 px-2 py-1.5">
            </div>
            <div class="mt-3 flex justify-end gap-2 text-xs">
                <button wire:click="$set('showClienteModal', false)" type="button" class="px-3 py-1 rounded-md border border-default-300">
                    Cancelar
                </button>
                <button wire:click="saveNuevoCliente" type="button" class="px-3 py-1 rounded-md bg-primary text-primary-foreground">
                    Guardar
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL NUEVO CONCEPTO --}}
    <div
        x-data
        x-cloak
        x-show="@js($showConceptoModal)"
        class="fixed inset-0 z-40 flex items-center justify-center bg-black/40"
    >
        <div class="w-full max-w-md rounded-md bg-card p-4 shadow-lg">
            <h2 class="text-sm font-semibold mb-2">Nuevo concepto</h2>
            <div class="space-y-2 text-xs">
                <input type="text" wire:model="nuevoConcepto.concepto" placeholder="Nombre concepto" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                <input type="text" wire:model="nuevoConcepto.grupo" placeholder="Grupo / categoría" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                <input type="text" wire:model="nuevoConcepto.unidad" placeholder="Unidad (UNID, NOCHE...)" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                <div class="grid grid-cols-3 gap-2">
                    <input type="number" step="0.01" wire:model="nuevoConcepto.precio" placeholder="Precio" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                    <input type="number" step="0.01" wire:model="nuevoConcepto.descuento" placeholder="Dto %" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                    <input type="number" step="0.01" wire:model="nuevoConcepto.impuesto" placeholder="IGIC %" class="w-full rounded-md border border-default-300 px-2 py-1.5">
                </div>
                <input type="number" step="0.01" wire:model="nuevoConcepto.retenciones" placeholder="Retención %" class="w-full rounded-md border border-default-300 px-2 py-1.5">
            </div>
            <div class="mt-3 flex justify-end gap-2 text-xs">
                <button wire:click="$set('showConceptoModal', false)" type="button" class="px-3 py-1 rounded-md border border-default-300">
                    Cancelar
                </button>
                <button wire:click="saveNuevoConcepto" type="button" class="px-3 py-1 rounded-md bg-primary text-primary-foreground">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
