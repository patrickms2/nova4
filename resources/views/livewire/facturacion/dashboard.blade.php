<?php

use App\Models\Factura;
use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Empresa;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Attributes\Layout;

new #[Title('Dashboard')] #[Layout('layouts.front')] class extends Component
{

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


@php
    $facturasTotal = \App\Models\Factura::count();
    $facturasPendientes = \App\Models\Factura::where('pagada', false)->count();
    $facturasPagadas = \App\Models\Factura::where('pagada', true)->count();
    $facturasImporte = \App\Models\Factura::sum('importe');
    $facturasImportePendiente = \App\Models\Factura::where('pagada', false)->sum('importe');
    $facturasVeriFactu = \App\Models\Factura::whereNotNull('verifactu_status')->count();
    $gastosTotal = \App\Models\Gasto::count();
    $gastosImporte = \App\Models\Gasto::sum('total');
    $clientesTotal = \App\Models\Cliente::count();
    $empresasTotal = \App\Models\Empresa::count();
    $conceptosTotal = \App\Models\Concepto::count();
@endphp
<div x-data="{ showFilters: false}; selectPrimary('dashboard') ">

<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-50">NovaFactu</h1>
            <p class="text-sm text-neutral-400">Panel de control de facturación y gastos</p>
        </div>
        <a href="{{ route('facturacion.nuevafactura') }}" class="inline-flex items-center gap-2 rounded-lg bg-neutral-50 px-4 py-2 text-sm font-medium text-black hover:bg-neutral-200">
            <x-lucide-plus class="h-4 w-4" />
            Nueva factura
        </a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Facturas emitidas</div>
            <div class="text-2xl font-semibold text-neutral-50">{{ number_format($facturasTotal, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Importe facturado</div>
            <div class="text-2xl font-semibold text-neutral-50">{{ number_format($facturasImporte, 0, ',', '.') }} €</div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Pendiente de cobro</div>
            <div class="text-2xl font-semibold text-amber-400">{{ number_format($facturasImportePendiente, 0, ',', '.') }} €</div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Gastos totales</div>
            <div class="text-2xl font-semibold text-rose-400">{{ number_format($gastosImporte, 0, ',', '.') }} €</div>
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Facturas pendientes</div>
            <div class="text-2xl font-semibold text-neutral-50">{{ number_format($facturasPendientes, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Facturas cobradas</div>
            <div class="text-2xl font-semibold text-emerald-400">{{ number_format($facturasPagadas, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Enviadas VeriFactu</div>
            <div class="text-2xl font-semibold text-indigo-400">{{ number_format($facturasVeriFactu, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Beneficio bruto</div>
            <div class="text-2xl font-semibold text-neutral-50">{{ number_format($facturasImporte - $gastosImporte, 0, ',', '.') }} €</div>
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Clientes</div>
            <div class="text-2xl font-semibold text-neutral-50">{{ number_format($clientesTotal, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Empresas</div>
            <div class="text-2xl font-semibold text-neutral-50">{{ number_format($empresasTotal, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Conceptos</div>
            <div class="text-2xl font-semibold text-neutral-50">{{ number_format($conceptosTotal, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-black p-5">
            <div class="mb-2 text-xs text-neutral-500">Gastos registrados</div>
            <div class="text-2xl font-semibold text-neutral-50">{{ number_format($gastosTotal, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('facturacion.facturas2') }}" class="group flex items-center gap-3 rounded-xl border border-neutral-800 bg-black p-4 hover:border-neutral-700">
            <x-lucide-file-text class="h-5 w-5 text-neutral-500 group-hover:text-neutral-50" />
            <span class="text-sm font-medium text-neutral-300">Ver facturas</span>
        </a>
        <a href="{{ route('facturacion.nuevafactura') }}" class="group flex items-center gap-3 rounded-xl border border-neutral-800 bg-black p-4 hover:border-neutral-700">
            <x-lucide-plus class="h-5 w-5 text-neutral-500 group-hover:text-neutral-50" />
            <span class="text-sm font-medium text-neutral-300">Nueva factura</span>
        </a>
        <a href="{{ route('facturacion.clientes') }}" class="group flex items-center gap-3 rounded-xl border border-neutral-800 bg-black p-4 hover:border-neutral-700">
            <x-lucide-users class="h-5 w-5 text-neutral-500 group-hover:text-neutral-50" />
            <span class="text-sm font-medium text-neutral-300">Clientes</span>
        </a>
        <a href="{{ route('facturacion.empresas') }}" class="group flex items-center gap-3 rounded-xl border border-neutral-800 bg-black p-4 hover:border-neutral-700">
            <x-lucide-building-2 class="h-5 w-5 text-neutral-500 group-hover:text-neutral-50" />
            <span class="text-sm font-medium text-neutral-300">Empresas</span>
        </a>
    </div>
</div>
<x-ui.sonner position="bottom-right" />

