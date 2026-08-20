<?php

namespace App\Livewire\Facturas;

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\ContadorFactura;
use App\Models\Empresa;

use App\Models\Factura;
use App\Models\Remesa;
use App\Services\Facturacion\PdfFacturaImporter;
use App\Services\Facturacion\VeriFactuService;
use App\Jobs\EnviarFacturaVeriFactu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Services\Facturacion\RemesaGenerator;
use App\Mail\FacturaPdfMail;
use Filament\Support\Assets\Css as AssetsCss;
use Illuminate\Support\Facades\Mail;

class Facturas extends Component
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

    public ?int $remesaFilter = null;

    public ?int $clienteFilter = null;

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

    public bool $showVeriFactuModal = false;

    public array $veriFactuDetail = [];

    public bool $showDeleteModal = false;

    public ?int $deleteFacturaId = null;

    public array $deleteFacturaIds = [];

    public bool $ajustarContador = false;

    public bool $showRemesaModal = false;

    public ?int $remesaSeleccionada = null;

    public bool $remesaConfirmarRegenerar = false;

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

    public function updatingClienteFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRemesaFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->clienteFilter = null;
        $this->remesaFilter = null;
        $this->fechaDesde = null;
        $this->fechaHasta = null;
        $this->resetPage();
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
        $this->clientes = Cliente::all()->toArray();
        $this->conceptos = $this->getConceptos();
        $this->empresas = $this->getEmpresas();
        $this->remesas = $this->getRemesas();

        }

    public function getConceptos()
    {

        return Concepto::query()
            ->when($this->search, function ($q) {
                $q->where('concepto', 'LIKE', "%{$this->search}%")
                    ->where('cliente_id', '=', "{$this->cliente_id}")
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
            ->get();
    }

    public function confirmDeleteFactura(int $id): void
    {
        $this->deleteFacturaId = $id;
        $this->ajustarContador = false;
        $this->showDeleteModal = true;
    }

    public function ejecutarDeleteFactura(): void
    {
        $anos = [];

        if ($this->deleteFacturaId) {
            $factura = Factura::findOrFail($this->deleteFacturaId);
            $anos[] = $factura->fechaemitido ? $factura->fechaemitido->year : now()->year;
            $factura->delete();
        } elseif (! empty($this->deleteFacturaIds)) {
            $facturas = Factura::query()->whereIn('id', $this->deleteFacturaIds)->get();
            $anos = $facturas->map(fn (Factura $f) => $f->fechaemitido ? $f->fechaemitido->year : now()->year)->unique()->all();
            Factura::query()->whereIn('id', $this->deleteFacturaIds)->delete();
            $this->selectedFacturas = [];
        } else {
            return;
        }

        if ($this->ajustarContador) {
            foreach (array_unique($anos) as $ano) {
                $this->ajustarContadorAno($ano);
            }
        }

        $this->showDeleteModal = false;
        $this->deleteFacturaId = null;
        $this->deleteFacturaIds = [];
        $this->ajustarContador = false;
        $this->dispatch('toast', type: 'success', title: 'Factura(s) eliminada(s).');
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteFacturaId = null;
        $this->deleteFacturaIds = [];
        $this->ajustarContador = false;
    }

    private function ajustarContadorAno(int $ano): void
    {
        $count = Factura::query()->whereYear('fechaemitido', $ano)->count();

        ContadorFactura::query()->updateOrCreate(
            ['ano' => $ano],
            ['contador' => $count]
        );
    }

    public function selectFactura(int $id): void
    {
        if (isset($this->selectedFacturas[$id])) {
            $this->selectedFacturas[$id] = false;

        } else {
            $this->selectedFacturas[$id] = true;
        }
    }

    public function toggleSelectAll(): void
    {
        $facturas = $this->getFacturas();
        $ids = $facturas->pluck('id')->all();

        if ($this->selectAll) {
            foreach ($ids as $id) {
                $this->selectedFacturas[$id] = true;
            }
            return;
        }

        $this->selectedFacturas = [];
    }

    public function confirmDeleteSelected(): void
    {
        $ids = array_keys(array_filter($this->selectedFacturas));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'warning', title: 'Selecciona al menos una factura.');
            return;
        }

        $this->deleteFacturaIds = $ids;
        $this->deleteFacturaId = null;
        $this->ajustarContador = false;
        $this->showDeleteModal = true;
    }

    public function deleteSelected(): void
    {
        $ids = array_keys(array_filter($this->selectedFacturas));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'warning', title: 'Selecciona al menos una factura.');
            return;
        }

        Factura::query()->whereIn('id', $ids)->delete();
        $this->selectedFacturas = [];
        $this->dispatch('toast', type: 'success', title: count($ids).' factura(s) eliminada(s).');
    }

    public function enviarVeriFactu(int $id): void
    {
        $factura = Factura::findOrFail($id);

        if ($factura->isVeriFactuSent()) {
            $this->dispatch('toast', type: 'warning', title: 'La factura ya está enviada a VeriFactu.');
            $this->verVeriFactu($id);
            return;
        }

        try {
            $result = app(VeriFactuService::class)->enviar($factura);
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', title: 'Error al enviar a VeriFactu', description: $e->getMessage());
            $this->verVeriFactu($id);
            return;
        }

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'Respuesta de AEAT sin mensaje';

        if ($status === 'success') {
            $this->dispatch('toast', type: 'success', title: 'Enviado a VeriFactu', description: $message);
        } elseif ($status === 'skipped') {
            $this->dispatch('toast', type: 'warning', title: 'Envío omitido', description: $message);
        } else {
            $this->dispatch('toast', type: 'error', title: 'Error de VeriFactu', description: $message);
        }

        $this->verVeriFactu($id);
    }

    public function verVeriFactu(int $id): void
    {
        $factura = Factura::findOrFail($id);

        $this->veriFactuDetail = [
            'codfactura' => $factura->codfactura,
            'status' => $factura->verifactu_status,
            'hash' => $factura->verifactu_hash,
            'previous_hash' => $factura->verifactu_previous_hash,
            'response_code' => $factura->verifactu_response_code,
            'response_message' => $factura->verifactu_response_message,
            'sent_at' => $factura->verifactu_sent_at?->format('d/m/Y H:i:s'),
            'qr_url' => $factura->verifactu_qr_url,
            'response_xml' => $this->formatXml($factura->verifactu_response_xml),
            'request_xml' => $this->formatXml($factura->verifactu_request_xml),
        ];

        $this->showVeriFactuModal = true;
    }

    private function formatXml(?string $xml): string
    {
        if (empty($xml)) {
            return 'No disponible';
        }

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (! @$dom->loadXML($xml)) {
            return $xml;
        }

        return $dom->saveXML() ?: $xml;
    }

    public function enviarVeriFactuSeleccionadas(): void
    {
        $ids = array_keys(array_filter($this->selectedFacturas));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'warning', title: 'Selecciona al menos una factura.');
            return;
        }

        $count = 0;
        foreach (Factura::query()->whereIn('id', $ids)->get() as $factura) {
            if ($factura->isVeriFactuSent()) {
                continue;
            }

            EnviarFacturaVeriFactu::dispatch($factura->id);
            $count++;
        }

        $this->selectedFacturas = [];
        $this->dispatch('toast', type: 'success', title: $count.' factura(s) encolada(s) para VeriFactu.');
    }

    public function recalcularSeleccionadas(): void
    {
        $ids = array_keys(array_filter($this->selectedFacturas));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'warning', title: 'Selecciona al menos una factura.');
            return;
        }

        $count = 0;
        foreach (Factura::with('registros')->whereIn('id', $ids)->get() as $factura) {
            $this->recalcularFactura($factura);
            $count++;
        }

        $this->selectedFacturas = [];
        $this->dispatch('toast', type: 'success', title: $count.' factura(s) recalculada(s).');
    }
    public function crearPdf(int $id): void
    {
        $factura = Factura::findOrFail($id);

        $directory = 'facturas-pdf';
        $guardados = [];

        $registros = $factura->registros;
        $pdf = Pdf::loadView('pdf.factura', compact('factura', 'registros'));
        $filename = public_path($directory.'/'.$factura->codfactura.'.pdf');
        $pdf->save($filename);
        $guardados[] = $factura->codfactura;
        $this->dispatch('download-pdf', url: '/'.$directory.'/'.$factura->codfactura.'.pdf');

        $this->dispatch('toast', type: 'success', title: 'PDF guardado en '.$directory.'/.');
    }
    public function generarPdfSeleccionadas(): void
    {
        $ids = array_keys(array_filter($this->selectedFacturas));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'warning', title: 'Selecciona al menos una factura.');
            return;
        }

        $directory = 'facturas-pdf';
        $guardados = [];

        foreach (Factura::with('registros')->whereIn('id', $ids)->get() as $factura) {
            $registros = $factura->registros;
            $pdf = Pdf::loadView('pdf.factura', compact('factura', 'registros'));
            $filename = public_path($directory.'/'.$factura->codfactura.'.pdf');
            $pdf->save($filename);
            $guardados[] = $factura->codfactura;
        }

        $this->selectedFacturas = [];
        $this->dispatch('toast', type: 'success', title: count($guardados).' PDF(s) guardado(s) en '.$directory.'/.');
    }

    private function recalcularFactura(Factura $factura): void
    {
        $base = 0;
        $igic = 0;
        $ret = 0;

        foreach ($factura->registros as $linea) {
            $cantidad = (float) $linea->cantidad;
            $precio = (float) $linea->precio;
            $dto = (float) $linea->descuento;
            $tipoI = (float) $linea->impuesto;
            $tipoR = (float) $linea->retenciones;

            $bruto = $cantidad * $precio;
            $baseLinea = $bruto - ($bruto * ($dto / 100));
            $valorI = $baseLinea * ($tipoI / 100);
            $valorR = $baseLinea * ($tipoR / 100);
            $importeLinea = $baseLinea + $valorI - $valorR;

            $linea->valorimpuesto = round($valorI, 2);
            $linea->valorretenciones = round($valorR, 2);
            $linea->importe = round($importeLinea, 2);
            $linea->save();

            $base += $baseLinea;
            $igic += $valorI;
            $ret += $valorR;
        }

        $factura->baseimponible = round($base, 2);
        $factura->impuesto = round($igic, 2);
        $factura->retenciones = round($ret, 2);
        $factura->importe = round($base + $igic - $ret, 2);
        $factura->save();
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
        return Remesa::orderBy('nombre')->withCount('facturas')->get();
    }

    public function newFactura(): void
    {
        $this->reset(['editingId']);
        $this->cliente_id = null;
        $this->conceptos = [];
        $this->form = [
            'cliente_id' => null,
            'remesa_id' => $this->remesaFilter ?: null,
            'codfactura' => Factura::suggestNumero(),
            'fechaemitido' => now()->format('Y-m-d'),
            'notas' => '',
            'observaciones' => '',
            'lineas' => [],
            'baseimponible' => 0,
            'baseexenta' => 0,
            'impuesto' => 0,
            'retenciones' => 0,
            'importe' => 0,
        ];

        $this->recalcularTotales();

        $this->showEditor = true;
    }
    public function openImportModal(): void
    {
        $this->pdfFile = null;
        $this->importMessage = '';
        $this->showImportModal = true;
    }

    public function importPdf(PdfFacturaImporter $importer): void
    {
        $this->importMessage = '';

        $this->validate([
            'pdfFile' => 'required|file|mimes:pdf|max:5120',
        ]);

        try {
            $result = $importer->import($this->pdfFile);
            $this->showImportModal = false;
            $this->pdfFile = null;
            $this->importMessage = 'Factura '.$result['codfactura'].' importada para '.$result['cliente_nombre'].'.';
            $this->dispatch('importOk', message: $this->importMessage);
        } catch (\Throwable $e) {
            $this->importMessage = 'Error al importar: '.$e->getMessage();
            Log::error('PDF import error', ['error' => $e->getMessage()]);
        }
    }

    public function duplicateFactura(int $id): void
    {

        $factura = Factura::with('registros')->findOrFail($id);
$factura->duplicate();

    }

    public function cancelarFactura(int $id): void
    {
        $factura = Factura::with('registros')->findOrFail($id);

        if ($factura->rectificativa) {
            $this->dispatch('toast', type: 'error', title: 'No se puede cancelar una rectificativa.');
            return;
        }

        if ($factura->rectificativas()->exists()) {
            $this->dispatch('toast', type: 'error', title: 'Esta factura ya tiene una rectificativa.');
            return;
        }

        $rectificativa = $factura->createRectificativa();

        $this->dispatch('toast', type: 'success', title: 'Rectificativa '.$rectificativa->codfactura.' creada.');
        $this->dispatch('download-pdf', url: route('factura.pdf', $rectificativa->codfactura));
    }
    public function enviaFactura(int $id): void
    {

        $factura = Factura::with('registros')->findOrFail($id);
        $email = $validated['email']
            ?? $factura->cliente?->email
            ?? 'patrickms@gmail.com';

        Mail::to($email)->send(new FacturaPdfMail($factura));

                $this->dispatch('toast', type: 'success', title: 'Factura(s) enviada(s).');
  }
    public function editFactura(int $id): void
    {
        $factura = Factura::with('registros')->findOrFail($id);
        $this->editingId = $factura->id;

        $this->form['cliente_id'] = $factura->cliente_id;
        $this->form['remesa_id'] = $factura->remesa_id;
        $this->form['codfactura'] = $factura->codfactura ?? '';
        $this->form['fechaemitido'] = optional($factura->fechaemitido)->format('Y-m-d');
        $this->form['notas'] = $factura->notas ?? '';
        $this->form['observaciones'] = $factura->observaciones ?? '';

        $this->cliente_id = $factura->cliente_id;
        if ($factura->cliente_id) {
            $this->conceptos = Concepto::query()
                ->where('cliente_id', $factura->cliente_id)
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
                ])->toArray();
        }

        $this->form['lineas'] = $factura->registros->map(function ($reg) {
            return [
                'concepto_id' => $reg->concepto_id,
                'descripcion' => $reg->descripcion,
                'cantidad' => (float) $reg->cantidad,
                'unidad' => $reg->unidad ?? 'UNID',
                'precio' => (float) $reg->precio,
                'descuento' => (float) ($reg->descuento ?? 0),
                'impuesto' => (float) ($reg->impuesto ?? 7),
                'retenciones' => (float) ($reg->retenciones ?? 15),
                'valorimpuesto' => (float) ($reg->valorimpuesto ?? 0),
                'valorretenciones' => (float) ($reg->valorretenciones ?? 0),
                'importe' => (float) $reg->importe,
            ];
        })->toArray();

        $this->form['baseimponible'] = (float) $factura->baseimponible;
        $this->form['baseexenta'] = (float) ($factura->baseexenta ?? 0);
        $this->form['impuesto'] = (float) $factura->impuesto;
        $this->form['retenciones'] = (float) $factura->retenciones;
        $this->form['importe'] = (float) $factura->importe;

        $this->showEditor = true;
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
                        ->where('cliente_id', '=', "{$this->cliente_id}")
                        ->where('concepto', 'like', "%{$q}%")
                        ->orWhere('codigo', 'like', "%{$q}%")
                        ->limit(10)
                        ->get(['id', 'concepto', 'precio', 'descuento', 'impuesto', 'retenciones', 'unidad'])
                        ->map(fn ($c) => [
                            'id' => $c->id,
                            'label' => $c->concepto,
                            'precio' => (float) ($c->precio ?? 0),
                            'descuento' => (float) ($c->descuento ?? 0),
                            'impuesto' => (float) ($c->impuesto ?? 7),
                            'retenciones' => (float) ($c->retenciones ?? 15),
                            'unidad' => $c->unidad ?? 'UNID',
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
                'id' => $c->id,
                'label' => $c->nombretotal.($c->dni ? " ({$c->dni})" : ''),
            ])
            ->toArray();
    }

    public function selectCliente(int $id): void
    {
        $this->cliente_id = $id;
        $this->form['cliente_id'] = $id;
        $cliente = Cliente::find($id);
        $this->clienteSearch = $cliente ? $cliente->nombretotal : '';
        $this->clienteSuggestions = [];
        $this->conceptos = $this->getConceptos();

    }

    // Autocomplete concepto
    public function openConceptoSearch(int $lineIndex): void
    {
        $this->conceptoLineaIndex = $lineIndex;
        $term = $this->form['lineas'][$lineIndex]['descripcion'] ?? '';
        $this->conceptoSearch = $term;
        $this->conceptoSuggestions = [];
        if (($this->cliente_id != '')) {
            $this->conceptoSuggestions = Concepto::query()
                // ->where('concepto', 'like', "%{$term}%")
                // ->orWhere('codigo', 'like', "%{$term}%")
                ->where('cliente_id', '=', "{$this->cliente_id}")
                ->limit(10)
                ->get(['id', 'concepto', 'precio', 'descuento', 'impuesto', 'retenciones', 'unidad'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'label' => $c->concepto,
                    'precio' => (float) ($c->precio ?? 0),
                    'descuento' => (float) ($c->descuento ?? 0),
                    'impuesto' => (float) ($c->impuesto ?? 7),
                    'retenciones' => (float) ($c->retenciones ?? 15),
                    'unidad' => $c->unidad ?? 'UNID',
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
            ->where('cliente_id', '=', "{$this->cliente_id}")
            // ->where('concepto', 'like', "%{$q}%")
            // ->orWhere('codigo', 'like', "%{$q}%")
            ->limit(10)
            ->get(['id', 'concepto', 'precio', 'descuento', 'impuesto', 'retenciones', 'unidad'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'label' => $c->concepto,
                'precio' => (float) ($c->precio ?? 0),
                'descuento' => (float) ($c->descuento ?? 0),
                'impuesto' => (float) ($c->impuesto ?? 7),
                'retenciones' => (float) ($c->retenciones ?? 15),
                'unidad' => $c->unidad ?? 'UNID',
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
        $this->form['lineas'][$lineIndex]['precio'] = (float) ($concepto->precio ?? 0);
        $this->form['lineas'][$lineIndex]['descuento'] = (float) ($concepto->descuento ?? 0);
        $this->form['lineas'][$lineIndex]['impuesto'] = (float) ($concepto->impuesto ?? 7);
        $this->form['lineas'][$lineIndex]['retenciones'] = (float) ($concepto->retenciones ?? 15);
        $this->form['lineas'][$lineIndex]['unidad'] = $concepto->unidad ?? 'UNID';

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
            'dni' => '',
            'email' => '',
            'telefono' => '',
            'domicilio' => '',
            'poblacion' => '',
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
            'concepto' => '',
            'descripcion' => '',
            'grupo' => '',
            'unidad' => 'UNID',
            'precio' => 0,
            'descuento' => 0,
            'impuesto' => 7,
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
            : new Factura;

        $factura->cliente_id = $data['cliente_id'];
        $factura->empresa_id = Cliente::find($data['cliente_id'])?->empresa_id;
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
        $this->dispatch('toast', type: 'success', title: 'Factura guardada correctamente.');
    }
  public function eliminaFactura(): void
    {
        $this->validate();
        $this->recalcularTotales();

        $data = $this->form;
        $factura = $this->editingId
            ? Factura::findOrFail($this->editingId)
            : new Factura;

        $factura->delete();
        $factura->registros()->delete();
       

        $this->showEditor = false;
        $this->resetPage();
        $this->dispatch('toast', type: 'success', title: 'Factura eliminada correctamente.');
    }
    public function openRemesaModal(): void
    {
        $this->showRemesaModal = true;
        $this->remesaSeleccionada = null;
        $this->remesaConfirmarRegenerar = false;
    }

    public function closeRemesaModal(): void
    {
        $this->showRemesaModal = false;
        $this->remesaSeleccionada = null;
        $this->remesaConfirmarRegenerar = false;
    }

    public function iniciarGenerarRemesa(): void
    {
        $remesa = Remesa::find($this->remesaSeleccionada);

        if (! $remesa) {
            $this->dispatch('toast', type: 'error', title: 'Selecciona una remesa.');
            return;
        }

        if ($remesa->facturas()->count() > 0) {
            $this->remesaConfirmarRegenerar = true;
            return;
        }

        $this->generarRemesa();
    }

    public function confirmarRegenerarRemesa(): void
    {
        $remesa = Remesa::find($this->remesaSeleccionada);

        if (! $remesa) {
            $this->dispatch('toast', type: 'error', title: 'Selecciona una remesa.');
            return;
        }

        DB::transaction(function () use ($remesa): void {
            $remesa->facturas()->delete();
            $remesa->remesaClientes()->update(['factura_id' => null]);
            $remesa->update(['estado' => 'draft']);
        });

        $this->generarRemesa();
    }

    public function generarRemesa(): void
    {
        $remesa = Remesa::find($this->remesaSeleccionada);

        if (! $remesa) {
            $this->dispatch('toast', type: 'error', title: 'Selecciona una remesa.');
            return;
        }

        $result = app(RemesaGenerator::class)->generate($remesa);

        $message = "Generadas {$result['created']} factura(s).";
        if ($result['skipped'] > 0) {
            $message .= " Omitidos {$result['skipped']} cliente(s).";
        }

        $type = count($result['errors']) > 0 ? 'warning' : 'success';

        $this->dispatch('toast', type: $type, title: $message);
        $this->closeRemesaModal();
    }

    public function render()
    {
        $facturasList = $this->getFacturas();
        $clientesFilter = Cliente::orderBy('nombretotal')->get();
        $remesasFilter = $this->getRemesas();

        return view('livewire.facturacion.facturas', compact(['facturasList', 'clientesFilter', 'remesasFilter']))
            ->layout('layouts.front');
    }
}
