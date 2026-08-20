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

    public string $search = '';
    public ?int $empresaFilter = null;
    public ?string $statusFilter = null;
    public ?string $conceptoFilter = null;
    public ?string $clienteFilter = null;

    public ?int $cliente_id = null;
    public array $lineas = [];
    public ?int $facturaId = null;
    public ?int $selectedConceptoId = null;
    public ?Concepto $selectedConcepto = null;
    public int $empresa_id = 1;
    public int $limit = 10;
    public int $lineIndex = 0;
    public string $numero = '';
    public string $serie = 'A';
    public ?string $fecha = null;
    public ?string $notas = null;

    public bool $showEditor = false;
    public ?int $editingId = null;

    public float $subtotal = 0;
    public float $total_igic = 0;
    public float $total_retencion = 0;
    public float $total_factura = 0;
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

    // Autocomplete conceptos (para una línea concreta)
    public string $conceptoSearch = '';
    public array $conceptoSuggestions = [];
    public ?int $conceptoLineaIndex = null;

    // Modales
    public bool $showClienteModal = false;
    public bool $showConceptoModal = false;


    public array $facturas = [];
    public array $facturas_registros = [];
    public array $clientes = [];
    public array $conceptos = [];
    public array $factura = [];
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
            ->get();
    }
    public function getConceptos()
    {

        return Concepto::query()
            ->when($this->search, function ($q) {
                $q->where('concepto', 'LIKE', "%{$this->search}%")
                    ->orWhere('codigo', 'LIKE', "%{$this->search}%")
                    ->orWhere('grupo', 'LIKE', "%{$this->search}%");
            })
            ->orderBy('concepto')
            ->limit($this->limit)
            ->get()->toArray();
    }
    public function getClientes()
    {
        if (strlen($this->searchc) < 2) {
            return collect();
        }

        $q = trim($this->searchc);

        return Cliente::query()
            ->when($this->searchc, function ($q) {
                $q
                    ->where('nombretotal', 'LIKE', "%{$this->searchc}%")
                    ->orWhere('nombre', 'LIKE', "%{$this->searchc}%")
                    ->orWhere('dni', 'LIKE', "%{$this->searchc}%")
                    ->orWhere('email', 'LIKE', "%{$this->searchc}%")
                    ->orWhere('telefono', 'LIKE', "%{$this->searchc}%");
            })
            ->orderBy('nombretotal')
            ->limit($this->limit)
            ->get()->toArray();
    }


    public function getFacturas()
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
            ->limit($this->limit)
            ->get()->toArray();
    }

    public function getEmpresasProperty()
    {
        return Empresa::orderBy('empresa')->get();
    }

    public function mount()
    {
        // Fecha por defecto: hoy
        $this->form['fechaemitido'] = now()->format('Y-m-d');
        $this->facturas = $this->getFacturas();
        $this->clientes = Cliente::all()->toArray();
        $this->conceptos = $this->getConceptos();
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

    public function buscar(): void
    {
        $this->facturas = $this->getFacturas();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'empresaFilter', 'statusFilter', 'conceptoFilter', 'clienteFilter', 'cliente_id']);
        $this->buscar();
    }
};

?>

<div class="py-8 max-w-7xl mx-auto">

    <h1 class="text-2xl font-bold mb-4">Gestión de Facturas</h1>
    <p class="text-sm text-default-500">Listado de facturas con búsqueda, filtros y editor en vivo.</p>

@if(session('message'))
        <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-white p-6 rounded shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <input
                type="text"
                wire:model.debounce.300ms="search"
                placeholder="Buscar por nº o cliente…"
                class="h-9 w-56 rounded-md border border-default-300 bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-primary"
            >
            <flux:select wire:model.live="filters.concepto" label="Conceptos" variant="listbox" placeholder="Concepto..." >
                <flux:select.option value="" wire:key="">Conceptos</flux:select.option>
                @foreach($this->conceptos as $concepto)
                    <flux:select.option value="{{ $concepto['codconcepto'] }}" wire:key="{{ $concepto['codconcepto'] }}">{{ $concepto['concepto'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="filters.cliente" label="Cliente" variant="listbox" placeholder="Cliente..." >
                <flux:select.option value="" wire:key="">Clientes</flux:select.option>
                @foreach($this->clientes as $cliente)
                    <flux:select.option value="{{ $cliente['codcliente'] }}" wire:key="{{ $cliente['codcliente'] }}">{{ $cliente['nombretotal'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <livewire:facturas.cliente-selector :cliente-id="$cliente_id" />
            <flux:select wire:model.live="empresaFilter"  label="Empresa"  variant="listbox" placeholder="Empresa..." >
                <flux:select.option value="" wire:key="">Empresas</flux:select.option>
                @foreach($this->empresas as $empresa)
                    <option value="{{ $empresa->id }}">{{ $empresa->empresa }}</option>
                    <flux:select.option value="{{ $empresa->id }}" wire:key="{{ $empresa->id }}">{{ $empresa->empresa }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input type="date" wire:model="filters.date_start" label="F. Inicio" />
            <flux:input type="date" wire:model="filters.date_end" label="F. Fin" />

            <button
                type="button"
                wire:click="newFactura"
                class="inline-flex items-center gap-1 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/80"
            >
                <span class="icon-[heroicons--plus-small] w-4 h-4"></span>
                Nueva factura
            </button>
            <div class="md:col-span-4 text-right">
                <flux:button  wire:click="openCreateModal" variant="primary" icon="plus"  color="green">Nueva Cita</flux:button>
                <flux:button  wire:click="buscar" icon="magnifying-glass" variant="primary" color="zinc">Buscar</flux:button>
                <flux:button  wire:click="resetFilters" variant="danger" color="red">Limpiar</flux:button>
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded shadow mb-6">


    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">

        </div>
    </div>



    {{-- MODAL NUEVO CLIENTE --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showClienteModal"
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
        x-show="$wire.showConceptoModal"
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
