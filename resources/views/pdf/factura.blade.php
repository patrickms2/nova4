<?php
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Factura;
use App\Models\RegistroFactura;
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
    public $facturas = [];
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




public function facturaPdf()
{
    $data = [
        'numero' => '000020/25',
        'fecha' => '21.05.2025',
        'observaciones' => 'ABRIL 2025',
        'cliente' => 'MICROMEDIA, S.L.',
        'cif' => 'B15328768',
        'direccion' => 'AVDA DE LUGO, 32, BAIXO',
        'telefono' => '981 570101',
        'lineas' => [
            [
                'fecha' => '21.05.2025',
                'descripcion' => 'MANTENIMIENTO',
                'detalle' => 'Expte AMT-2022-044 -L5 - Abril 2025',
                'cant' => 1,
                'unidad' => 1,
                'precio' => 125,
                'dto' => 0,
                'imp' => 0,
                'ret' => 15,
                'importe' => 106.25,
            ],
            [
                'fecha' => '21.05.2025',
                'descripcion' => 'MANTENIMIENTO',
                'detalle' => 'Expte AMT-2023-0082 - Abril 2025',
                'cant' => 1,
                'unidad' => 1,
                'precio' => 125,
                'dto' => 0,
                'imp' => 0,
                'ret' => 15,
                'importe' => 106.25,
            ],
        ],
    ];

    $html = view('pdf.factura-novagestion', $data)->render();

    return response(
        Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(0, 0, 0, 0)
            ->pdf() )->header('Content-Type', 'application/pdf');
}

    public function mount(): void
    {


        // Fecha por defecto: hoy
        $this->form['fechaemitido'] = now()->format('Y-m-d');
        $this->facturas = $this->getFacturas();
        $this->clientes = Cliente::all()->toArray();
        $this->conceptos = $this->getConceptos();
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
            ->get();
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

    public function updated($name, $value): void
    {
        if (str_contains($name, 'descripcion')) {
            $parts = explode('.', $name);
            if (count($parts) >= 3) {
                $index = (int) $parts[2];
                $this->conceptoLineaIndex = $index;
                $this->conceptoSearch = $value;

                $q = trim($value);
                if ($q === '') {
                    $this->conceptoSuggestions = [];
                } else {
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
            }
        }
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
        $term = $this->form['lineas'][$lineIndex]['descripcion'] ?? '';
        $this->conceptoSearch = $term;
        $this->conceptoSuggestions = [];
        
        if ($term !== '') {
            $this->conceptoSuggestions = Concepto::query()
                ->where('concepto', 'like', "%{$term}%")
                ->orWhere('codigo', 'like', "%{$term}%")
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura->codfactura }}</title>
    <style>
        @page {
            margin: 1.2cm 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            margin: auto;
        }
        .rounded-box {
            border: 1px solid #343333;
            border-radius: 16px;
            padding: 12px 18px;
            margin-bottom: 12px;
            max-width: 880px;
            margin: 15px auto;
        }
        .text-orange {
            color: #df8026;
        }
        .bg-orange {
            background-color: #df8026;
        }
        .table-lines {
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 12px;
                        width: 550px;
            border-radius: 16px;

             margin: 15px auto;
        }
        .table-lines th {
            color: #df8026;
            font-weight: bold;
            text-align: left;
            padding: 6px 4px;
            font-size: 14px;
padding: 12px 18px;
        }
        .table-lines td {
            padding: 8px 4px;
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .nota-final {
            text-align: center;
            font-size: 9.5px;
            color: #555;
            margin-top: 15px;
        }
        .visor {
            background: #ffffff;
            margin: 0 auto;
            max-width: 720px;
            height: 880px;
            padding: 5px;

        }
        .rounded-box-bottom {
            border-top: 2px solid #343333;

    padding-top: 25px !important;
    position: relative;
    bottom: 0;
    display: inline-grid-lanes;
    max-width: 720px;
    width: 100%;
    margin: auto;
        }
    </style>
</head>
<body style="background: #1a1a1a;">
    
        <div class="visor" style="height:1025px;">

    <!-- Box 1: Cabecera Emisor -->
    <div class="rounded-box" style="width: 670px;">

        <table style="width: 100%; border-collapse: collapse; border: none;">
            <tr>
                <td style="width: 30%; vertical-align: middle; border: none;">

                    @if($factura->cliente->empresa_id === 1)
                        <img src="/logo_h.jpg" width="125" style="display: block;width: 175px;padding-left: 25px;padding-right: 25px;" alt="Logo">
                    @else
                        <img src="/logo_lavadigital.jpg" style=" display: block;width: 285px;" alt="Logo">
                    @endif


                </td>
                <td style="width: 30%; text-align: center; vertical-align: middle; border: none;">
                    <span style="font-size: 22px; font-decoration: underline; font-weight: bold; color: #df8026; letter-spacing: 1px;">FACTURA</span>
                </td>
                <td style="width: 40%;text-align: right;padding-right: 15px;padding-top: 15px;font-size: 11px;line-height: 1.35;color: #444;vertical-align: middle;border: none;">
                    <div style="font-weight: bold; font-size: 13px; color: #333; margin-bottom: 2px;">Patrick Axel Müller Suárez</div>
                    <div>NIF: 45532522CC</div>
                    <div>Piragua, 3 - Costa Teguise 35509</div>
                    <div>Lanzarote - España</div>
                    <div>T: +34646426442 / E: patrickms@gmail.com</div>
                    <div>IBAN ES69 1583 0001 1990 9448 5695</div>
                    <div style="font-size: 9px;">SWIFT / BIC: REVOESM2</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Box 2: Datos Factura y Cliente -->
    <div class="rounded-box" style="width: 670px;">
        <table style="width: 100%; border-collapse: collapse; border: none; font-size: 12px; line-height: 1.6;">
            <tr>
                <td style="width: 55%; vertical-align: top; border: none;">
                    <div><strong>Nº:</strong> {{ $factura->codfactura }}</div>
                    <div style="margin-top: 10px;"><strong>Cliente:</strong> {{ $factura->cliente_nombre ?? optional($factura->cliente)->nombretotal }}</div>
                    <div style="margin-top: 3px;"><strong>Dirección:</strong> {{ $factura->cliente_direccion ?? optional($factura->cliente)->domicilio }}</div>
                    @if ($factura->observaciones)
                        <div style="margin-top: 3px;"><strong>Observaciones:</strong> {{ $factura->observaciones ?? '' }}</div>
                    @endif

                </td>
                <td style="width: 45%; vertical-align: top; text-align: right; border: none;">
                    <div><strong>Fecha emisión:</strong> {{ optional($factura->fechaemitido)->format('d.m.Y') }}</div>
                    <div style="margin-top: 10px; font-weight: bold;">CIF: {{ $factura->cliente_cif ?? optional($factura->cliente)->dni }}</div>
                    <div style="margin-top: 3px;"><strong>Teléfonos:</strong> {{ $factura->cliente_telefono ?? optional($factura->cliente)->telefono }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="table-lines" style="width: 620px;">
        <thead >

            <?php
            $registros = \App\Models\RegistroFactura::with('concepto')->where('factura_id', $factura->id)->get();

?>
            <tr style="border-bottom: 2px solid #7c7b79; borde-radius: 16px">
                <th class="text-left" style="width: 8%;text-align: center;padding: 0; color: #df8026;">Fecha</th>
                <th class="text-left" style="width: 53%;">Descripción</th>
                <th class="text-center" style="width: 6%;">Cant.</th>
                                    @if($registros[0]->unidad)
                <th class="text-center" style="width: 6%;">Unid.</th>
                    @endif

                <th class="text-right" style="width: 8%;">Precio</th>
                <th class="text-center" style="width: 6%;">Imp.</th>
                <th class="text-center" style="width: 6%;">Ret.</th>
                <th class="text-right" style="width: 10%;">Importe</th>
            </tr>
        </thead>
        <tbody>

            @foreach($registros as $linea)
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="white-space: nowrap;">
                        {{ optional($linea->fecha ?? $factura->fechaemitido)->format('d.m.Y') }}
                    </td>
                    <td style="line-height: 1.35;padding-left: 10px;">
                        <div class="flex" style="color: #111;font-size: 12px !important;display: block;text-wrap-mode: nowrap;flex-wrap: wrap;flex-direction: row;justify-content: flex-start;align-content: space-around;align-items: flex-start;">
                          @php
                              $concepto = $linea->concepto->concepto ?? '';
                              $concepto = str_replace(' / ', '<br/>', $concepto);
                          @endphp
                            {!! $concepto !!} @if(!empty($linea->descripcion))<div style="color: #111;font-weight: 600;display: block;text-wrap-mode: nowrap;font-size: 12px !important;vertical-align: middle !important;align-items: center;margin-left: 0px;">{{ $linea->descripcion }}</div>@endif
                        </div>
                        @if(!empty($linea->observaciones))
                            <div style="font-size: 9px; color: #666; margin-top: 2px;">{{ $linea->observaciones }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ number_format($linea->cantidad ?? 1, 0) }}
                    </td>
                    @if($linea->unidad)
                    <td class="text-center">
                        {{ $linea->unidad ?? '1' }}
                    </td>
                    @endif
                    <td class="text-center">
                        {{ number_format($linea->precio ?? 0, 2, ',', '.') }}
                    </td>
                    <td class="text-center">
                        {{ number_format($linea->valorimpuesto ?? 7, 2, ',', '.') }}
                    </td>
                    <td class="text-center">
                        {{ number_format($linea->valorretenciones ?? 15, 2, ',', '.') }}
                    </td>
                    <td class="text-center" style="font-weight: bold; font-size: 14px;">
                        {{ number_format($linea->importe ?? 0, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Box 3: Totales -->
    <div class="rounded-box-bottom" style="
    margin-top: 380px !important;
    position: relative;
    bottom: 0;
    display: inline-grid-lanes;
    max-width: 620px;
    width: 100%;
    margin: auto;">
        <table style="width: 100%; border-collapse: collapse; border: none; font-size: 14px; line-height: 1.6;">
            <tr>
                <td style="width: 35%; vertical-align: middle; border: none;">
                    <div><strong>B. Exenta:</strong> {{ number_format($factura->baseexenta ?? 0, 2, ',', '.') }} €</div>
                    <div style="margin-top: 3px;"><strong>B. Imponible 7%:</strong> {{ number_format($factura->baseimponible ?? 0, 2, ',', '.') }} €</div>
                </td>
                <td style="width: 35%; vertical-align: middle; border: none; padding-left: 15px;">
                    <div><strong>Retenciones:</strong> {{ number_format($factura->retenciones ?? 0, 2, ',', '.') }} €</div>
                    <div style="margin-top: 3px;"><strong>IGIC 7%:</strong> {{ number_format($factura->impuesto ?? 0, 2, ',', '.') }} €</div>
                </td>
                <td style="width: 30%; vertical-align: middle; text-align: right; border: none; font-size: 14px;">
                    <strong>Importe:</strong> <span style="color: #df8026; font-size: 17px; font-weight: bold; margin-left: 5px;">{{ number_format($factura->importe ?? 0, 2, ',', '.') }} €</span>
                </td>
            </tr>
        </table>
            <!-- Nota Pie de Página -->
    <div class="nota-final">
       2 Régimen Especial del Criterio de caja. La factura se entenderá pagada cuando conste el abono del importe total.
    </div>
    </div>



</body>
</html>
