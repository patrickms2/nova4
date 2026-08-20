<?php

namespace App\Livewire\Facturas;

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Empresa;

use App\Models\Factura;
use App\Models\Remesa;
use App\Services\Facturacion\PdfFacturaImporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.front')] class extends Component
{
    use WithFileUploads;
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

    public ?string $fechaDesde = null;

    public ?string $fechaHasta = null;

    public ?string $statusFilter = null;

    public bool $showEditor = false;

    public ?int $editingId = null;

    public array $form = [
        'cliente_id' => null,
        'remesa_id' => null,
        'codfactura' => '',
        'fechaemitido' => '',
        'notas' => '',
        'observaciones' => '',
        'lineas' => [],
        'baseimponible' => 0,
        'baseexenta' => 0,
        'impuesto' => 0,
        'retenciones' => 0,
        'importe' => 0,
    ];

    // Autocomplete cliente
    public string $clienteSearch = '';

    public array $clienteSuggestions = [];

    public $facturas = [];

    public ?int $remesaFilter = null;
    public $remesasFilter = [];

    public ?int $clienteFilter = null;

    public array $facturas_registros = [];

    /** @var array<int, bool> */
    public array $selectedFacturas = [];

    public bool $selectAll = false;

    public $pdfFile = null;

    public bool $showImportModal = false;

    public string $importMessage = '';

    public $clientes = [];

    public array $conceptos = [];

    public $empresas = [];
    public $remesas = [];

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
        'dni' => '',
        'email' => '',
        'telefono' => '',
        'domicilio' => '',
        'poblacion' => '',
    ];

    public array $nuevoConcepto = [
        'concepto' => '',
        'grupo' => '',
        'cliente_id' => null,
        'unidad' => 'UNID',
        'precio' => 0,
        'descuento' => 0,
        'impuesto' => 7,
        'retenciones' => 15,
    ];

    protected $rules = [
        'form.cliente_id' => 'required|exists:clientes,id',
        'form.fechaemitido' => 'required|date',
        'form.lineas' => 'required|array|min:1',
        'form.lineas.*.descripcion' => 'required|string',
        'form.lineas.*.cantidad' => 'required|numeric|min:0.01',
        'form.lineas.*.precio' => 'required|numeric|min:0',
        'form.lineas.*.descuento' => 'nullable|numeric|min:0',
        'form.lineas.*.impuesto' => 'nullable|numeric|min:0',
        'form.lineas.*.retenciones' => 'nullable|numeric|min:0',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage();
    }
    public function getFacturas()
    {
        return Factura::query()
            ->with('cliente', 'remesa')
            ->orderBy('facturas.id','desc')
            ->when($this->search, fn ($q) => $q->where('codfactura', 'like', "%{$this->search}%")
                ->orWhereHas('cliente', fn ($qq) => $qq->where('nombretotal', 'like', "%{$this->search}%")
                    ->orWhere('dni', 'like', "%{$this->search}%")
                )
            )
            ->when($this->clienteFilter, fn ($q) => $q->where('cliente_id', $this->clienteFilter))
            ->when($this->remesaFilter, fn ($q) => $q->where('remesa_id', $this->remesaFilter))
            ->when($this->fechaDesde, fn ($q) => $q->whereDate('fechaemitido', '>=', $this->fechaDesde))
            ->when($this->fechaHasta, fn ($q) => $q->whereDate('fechaemitido', '<=', $this->fechaHasta))
            ->latest('fechaemitido')
            ->get();
    }

    public function getEmpresas()
    {
        return Empresa::orderBy('empresa')->get();
    }

        public function getRemesas()
    {
        return Remesa::orderBy('nombre')->get();
    }
    public function clearFilters(): void
    {
        $this->search = '';
        $this->fechaDesde = null;
        $this->fechaHasta = null;
        $this->resetPage();
    }


    public function save()
    {
        $this->validate();
        $this->recalcularTotales();

        $data = $this->form;
        $factura = $this->editingId
            ? Factura::findOrFail($this->editingId)
            : new Factura;

        $factura->cliente_id = $data['cliente_id'];
        $factura->remesa_id = $data['remesa_id'] ?: null;
        $factura->fechaemitido = Carbon::parse($data['fechaemitido']);
        $factura->baseimponible = $data['baseimponible'];
        $factura->baseexenta = $data['baseexenta'];
        $factura->impuesto = $data['impuesto'];
        $factura->retenciones = $data['retenciones'];
        $factura->importe = $data['importe'];
        $factura->notas = $data['notas'] ?? null;
        $factura->observaciones = $data['observaciones'] ?? null;
        $factura->save();
        // Regenerar líneas
        $factura->registros()->delete();
        foreach ($data['lineas'] as $linea) {
            $factura->registros()->create([
                'concepto_id' => $linea['concepto_id'],
                'descripcion' => $linea['descripcion'],
                'cantidad' => $linea['cantidad'],
                'unidad' => $linea['unidad'],
                'precio' => $linea['precio'],
                'descuento' => $linea['descuento'],
                'impuesto' => $linea['impuesto'],
                'retenciones' => $linea['retenciones'],
                'valorimpuesto' => $linea['valorimpuesto'],
                'valorretenciones' => $linea['valorretenciones'],
                'importe' => $linea['importe'],
                'fecha' => $data['fechaemitido'],
            ]);
        }

        $this->showEditor = false;
        $this->resetPage();
        $facturasList = $this->getFacturas();
        $clientesFilter = Cliente::orderBy('nombretotal')->get();
        $remesasFilter = $this->getRemesas();
        return redirect()->route('facturacion.facturas2', compact(['facturasList', 'clientesFilter', 'remesasFilter']));
        $this->dispatch('notify', type: 'success', message: 'Factura guardada correctamente.');
    }
    public function updatedFormClienteId(?string $value): void
    {
        $id = filled($value) ? (int) $value : null;
        $this->cliente_id = $id;
        $this->form['lineas'] = [];
        $this->recalcularTotales();

        if ($id) {
            $this->conceptos = Concepto::query()
                ->where('cliente_id', $id)
                ->orderBy('concepto')
                ->get(['id', 'concepto', 'codigo', 'precio', 'descuento', 'impuesto', 'retenciones', 'unidad'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'label' => $c->concepto,
                    'codigo' => $c->codigo ?? '',
                    'precio' => (float) ($c->precio ?? 0),
                    'descuento' => (float) ($c->descuento ?? 0),
                    'impuesto' => (float) ($c->impuesto ?? 7),
                    'retenciones' => (float) ($c->retenciones ?? 15),
                    'unidad' => $c->unidad ?? 'UNID',
                ])
                ->toArray();

            $this->addLinea();
        } else {
            $this->conceptos = [];
        }
    }

    public function selectConceptoParaLinea(int $lineIndex, int $conceptoId): void
    {
        $concepto = Concepto::findOrFail($conceptoId);

        $this->form['lineas'][$lineIndex]['concepto_id'] = $concepto->id;
        $this->form['lineas'][$lineIndex]['codigo'] = $concepto->codigo ?? '';
        $this->form['lineas'][$lineIndex]['descripcion'] = $this->mesEnEspanol();
        $this->form['lineas'][$lineIndex]['precio'] = (float) ($concepto->precio ?? 0);
        $this->form['lineas'][$lineIndex]['descuento'] = (float) ($concepto->descuento ?? 0);
        $this->form['lineas'][$lineIndex]['impuesto'] = (float) ($concepto->impuesto ?? 7);
        $this->form['lineas'][$lineIndex]['retenciones'] = (float) ($concepto->retenciones ?? 15);
        $this->form['lineas'][$lineIndex]['unidad'] = $concepto->unidad ?? 'UNID';

        $this->recalcularTotales();
    }
     public function addLinea(): void
    {
        $this->form['lineas'][] = [
            'concepto_id' => null,
            'descripcion' => $this->mesEnEspanol(),
            'cantidad' => 1,
            'unidad' => 'UNID',
            'precio' => 0,
            'descuento' => 0,
            'impuesto' => 7,
            'retenciones' => 15,
            'valorimpuesto' => 0,
            'valorretenciones' => 0,
            'importe' => 0,
        ];

        $this->recalcularTotales();
    }

    protected function recalcularTotales(): void
    {
        $base = 0;
        $igic = 0;
        $ret = 0;
        $importe = 0;

        foreach ($this->form['lineas'] as $i => &$linea) {
            $cantidad = (float) ($linea['cantidad'] ?? 0);
            $precio = (float) ($linea['precio'] ?? 0);
            $dto = (float) ($linea['descuento'] ?? 0);
            $tipoI = (float) ($linea['impuesto'] ?? 7);
            $tipoR = (float) ($linea['retenciones'] ?? 15);

            $bruto = $cantidad * $precio;
            $importeDto = $bruto * ($dto / 100);
            $baseLinea = $bruto - $importeDto;

            $valorI = $baseLinea * ($tipoI / 100);
            $valorR = $baseLinea * ($tipoR / 100);
            $importe = $baseLinea + $valorI - $valorR;
            $linea['valorimpuesto'] = round($valorI, 2);
            $linea['valorretenciones'] = round($valorR, 2);
            $linea['importe'] = round($importe, 2);

            $base += $baseLinea;
            $igic += $valorI;
            $ret += $valorR;
        }

        $this->form['baseimponible'] = round($base, 2);
        $this->form['baseexenta'] = round((float) ($this->form['baseexenta'] ?? 0), 2);
        $this->form['impuesto'] = round($igic, 2);
        $this->form['retenciones'] = round($ret, 2);
        $this->form['importe'] = round($base + $igic - $ret, 2);

    }
    private function mesEnEspanol(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $fecha = filled($this->form['fechaemitido'])
            ? Carbon::parse($this->form['fechaemitido'])
            : now();

        return $meses[$fecha->month - 1];
    }
    public function removeLinea(int $index): void
    {
        unset($this->form['lineas'][$index]);
        $this->form['lineas'] = array_values($this->form['lineas']);
        $this->recalcularTotales();
    }
    public function mount(): void
    {
        $this->fechaDesde = now()->startOfYear()->format('Y-m-d');
        $this->fechaHasta = now()->endOfYear()->format('Y-m-d');
        $this->init();
    }

    public function init()
    {

        // Fecha por defecto: hoy
        $this->form['fechaemitido'] = now()->format('Y-m-d');
        $this->facturas = $this->getFacturas();
        $this->clientes = Cliente::orderBy('nombretotal')->get();
        $this->remesas = $this->getRemesas();

        $this->showEditor = true;
    }

}
?>
{{-- EDITOR SLIDE-IN — BlatUI Sheet --}}
    <x-ui.sheet entangle="$wire.entangle('showEditor')" x-cloak>
        <x-ui.sheet-content
            side="right"
            :show-close="false"
            class="w-screen max-w-5xl flex flex-col gap-0 p-0 overflow-hidden"
        >
            {{-- Header BlatUI --}}
            <x-ui.sheet-header class="shrink-0 flex flex-row items-center justify-between px-4 py-2.5 border-b gap-0">
                <x-ui.sheet-title class="flex flex-wrap text-sm">
                    @if($editingId) Editar Factura @else Nueva Factura @endif


 {{-- Cod. Factura --}}
                    <div class="flex flex-1 items-top gap-4 ml-4 mt-1">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Nº Factura</label>
                        <x-ui.input
                            size="sm"
                            wire:model="form.codfactura"
                            placeholder="00001_2025"
                            class="w-38 font-mono"
                        />
                    </div>

                    {{-- Fecha --}}
                    <div class="flex flex-1 items-top gap-4 ml-4 mt-1">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Fecha</label>
                    <x-ui.date-picker
                            number-of-months="1"
                            :max="now()->format('Y-m-d')"
                             wire:model="form.fechaemitido"
                            width="w-72"
                        />
                        @error('form.fechaemitido') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                </x-ui.sheet-title>
                <button type="button" @click="open = false"
                    class="rounded-md p-1.5 text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
                    <x-lucide-x class="size-4" />
                </button>
            </x-ui.sheet-header>

            {{-- Barra de campos: Cliente · Remesa · Observaciones --}}
            <div class="shrink-0 bg-muted/40 border-b px-4 py-2">
                <div class="flex flex-wrap items-end gap-x-4 gap-y-2">
                    {{-- Cliente --}}
                    <div class="flex flex-1 items-center gap-2 min-w-0">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Cliente</label>
                        <x-ui.select native size="sm" wire:model.live="form.cliente_id"
                            x-init="$el.focus()"
                            tabindex="1"
                            class="flex-1 min-w-0"
                        >
                            <option value="">— Seleccionar cliente —</option>
                            @foreach($this->clientes as $cliente)
                                <option value="{{ $cliente['id'] }}" @selected($form['cliente_id'] == $cliente['id'])>{{ $cliente['nombretotal'] }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('form.cliente_id') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                    {{-- Remesa --}}
                    <div class="flex items-center gap-2 w-48">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Remesa</label>
                        <x-ui.select native size="sm" wire:model="form.remesa_id" class="flex-1 min-w-0">
                            <option value="">— Ninguna —</option>
                            @foreach($remesas as $r)
                                <option value="{{ $r->id }}" @selected($form['remesa_id'] == $r->id)>{{ $r->nombre }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    {{-- Observaciones --}}
                    <div class="flex flex-1 items-center gap-2 min-w-0">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Observaciones</label>
                        <x-ui.input size="sm" wire:model="form.observaciones" placeholder="Notas internas de la factura" class="flex-1 min-w-0" />
                    </div>
                </div>
            </div>

            {{-- Sub-barra "Detalle de venta" + botón añadir --}}
            <div class="shrink-0 flex items-center justify-between bg-muted border-b px-4 py-1">
                <span class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Detalle de venta · Conceptos</span>
                @if($form['cliente_id'])
                    <x-ui.button variant="secondary" wire:click="addLinea" tabindex="-1" class="h-6 gap-1 px-2 text-[11px]">
                        <x-lucide-plus class="size-3" />
                        Añadir línea
                    </x-ui.button>
                @endif
            </div>

            {{-- Área de líneas (scrollable) --}}
            <div class="flex-1 overflow-y-auto">

                @if(!$form['cliente_id'])
                    {{-- Sin cliente seleccionado --}}
                    <x-ui.empty class="h-full border-0 rounded-none text-muted-foreground">
                        <x-lucide-users class="size-10 opacity-30 mx-auto mb-1" />
                        <p class="text-sm font-medium">Selecciona un cliente</p>
                        <p class="text-xs opacity-60">Los conceptos disponibles aparecerán aquí</p>
                    </x-ui.empty>

                @else
                    {{-- Cabecera columnas --}}
                    <div class="grid items-center text-[10px] font-bold text-muted-foreground uppercase tracking-wider bg-muted/60 border-b px-3 py-1"
                         style="grid-template-columns: 2fr 2fr 4rem 4rem 4rem 4rem 4rem 4rem 5rem 1.75rem">
                        <span>Concepto</span>
                        <span>Descripción</span>
                        <span class="text-left">Cant.</span>
                        <span class="text-left">Unidad</span>
                        <span class="text-left">Precio</span>
                        <span class="text-left">Dto.%</span>
                        <span class="text-left">IGIC%</span>
                        <span class="text-left">Ret.%</span>
                        <span class="text-left">Importe</span>
                        <span></span>
                    </div>

                    {{-- Filas --}}
                    <div class="divide-y divide-border">
                        @forelse($form['lineas'] as $index => $linea)
                            <div class="grid items-center gap-1 px-3 py-1.5 hover:bg-accent/30 transition-colors"
                                 style="grid-template-columns: 2fr 1fr 4rem 4rem 4rem 4rem 4rem 4rem 5rem 1.75rem">

                                {{-- Selector de concepto filtrado por cliente --}}
                                <x-ui.select native size="sm"
                                    wire:model.lazy="form.lineas.{{ $index }}.concepto_id"
                                    x-on:change="$wire.selectConceptoParaLinea({{ $index }}, $event.target.value)"
                                    class="w-full"
                                    tabindex="{{ 8 + $index * 7 }}" >
                                                            <option value="">— Concepto —</option>

                                    @foreach($this->conceptos as $c)
                                        <option value="{{ $c['id'] }}" @selected(($linea['concepto_id'] ?? null) == $c['id'])>
                                            {{ $c['label'] }}
                                        </option>
                                    @endforeach
                                </x-ui.select>

                                {{-- Descripción --}}
                                <x-ui.input size="sm" type="text"
                                    wire:model.lazy="form.lineas.{{ $index }}.descripcion"
                                    placeholder="Mes / concepto…"
                                    tabindex="{{ 9 + $index * 7 }}" />

                                {{-- Cantidad --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.cantidad"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 10 + $index * 6 }}" />

                                {{-- Unidad --}}
                                <x-ui.input size="sm" type="text"
                                    wire:model.lazy="form.lineas.{{ $index }}.unidad"
                                    class="text-right"
                                    tabindex="{{ 11 + $index * 6 }}" />

                                {{-- Precio --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.precio"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 12 + $index * 6 }}" />

                                {{-- Dto % --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.descuento"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 13 + $index * 6 }}" />

                                {{-- IGIC % --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.impuesto"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 14 + $index * 6 }}" />

                                {{-- Ret % --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.retenciones"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 15 + $index * 6 }}" />

                                {{-- Importe (read-only) --}}
                                <div class="text-right text-sm font-semibold tabular-nums text-foreground pr-1">
                                    {{ number_format($linea['importe'] ?? 0, 2, ',', '.') }} €
                                </div>

                                {{-- Eliminar --}}
                                <button type="button" wire:click="removeLinea({{ $index }})"
                                    class="flex items-center justify-center text-destructive/60 hover:text-destructive rounded hover:bg-destructive/10 p-1 transition-colors cursor-pointer"
                                    title="Eliminar línea">
                                    <x-lucide-x class="size-3.5" />
                                </button>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-muted-foreground">
                                Sin líneas. Pulsa <kbd class="px-1.5 py-0.5 rounded border text-[10px] font-mono bg-muted">Añadir línea</kbd> para comenzar.
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            {{-- Barra de totales --}}
            <div class="shrink-0 border-t-2 border-border bg-muted/50 px-4 py-2">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-xs">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Base Exenta</span>
                        <span class="font-semibold tabular-nums">{{ number_format($form['baseexenta'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">B. Imponible</span>
                        <span class="font-semibold tabular-nums">{{ number_format($form['baseimponible'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">IGIC</span>
                        <span class="font-semibold tabular-nums">{{ number_format($form['impuesto'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Retenciones</span>
                        <span class="font-semibold tabular-nums">-{{ number_format($form['retenciones'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="ml-auto flex flex-col items-end">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Total Factura</span>
                        <span class="text-xl font-extrabold tabular-nums text-destructive">{{ number_format($form['importe'], 2, ',', '.') }} €</span>
                    </div>
                </div>
            </div>

            {{-- Footer: acciones --}}
            <x-ui.sheet-footer class="shrink-0 border-t px-4 py-2 flex-row justify-between gap-2">
                <x-ui.button variant="secondary" type="button" @click="open = false">
                    Cancelar
                </x-ui.button>
                <x-ui.button  variant="secondary" class="gap-1.5">
                    <x-lucide-file-text class="size-4" />
                    <a href="{{ route('factura.pdf', $form['codfactura']) }}" target="_blank" class="w-full">PDF</a>
                </x-ui.button>
                <x-ui.button  variant="secondary" wire:click="eliminaFactura" class="gap-1.5">
                    <x-lucide-x class="size-4" />
                    Eliminar factura
                </x-ui.button>
                <x-ui.button wire:click="save" class="gap-1.5">
                    <x-lucide-check class="size-4" />
                    Guardar factura
                </x-ui.button>
            </x-ui.sheet-footer>

        </x-ui.sheet-content>
    </x-ui.sheet>
