<?php

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Factura;
use App\Models\NovaBundleOrder;
use App\Models\NovaExternalCatalogItem;
use App\Services\Nova\NovaBundleOrderService;
use App\Services\Nova\NovaMagentoApiSyncService;
use App\Services\Nova\NovaWooCommerceApiSyncService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public string $status = '';

    public bool $showCreateModal = false;

    public bool $showInvoiceModal = false;

    public ?int $invoiceBundleId = null;

    public array $form = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'city' => '',
        'postcode' => '',
        'country' => 'ES',
        'region_id' => 157,
        'region_code' => 'Las Palmas',
        'region' => 'Las Palmas',
        'company' => '',
        'la_geria_product_id' => '',
        'la_geria_quantity' => 2,
        'lanzaloe_sku' => '',
        'lanzaloe_quantity' => 1,
    ];

    public array $syncResult = [];

    public function with(): array
    {
        $query = NovaBundleOrder::query()->latest();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('bundle_reference', 'like', "%{$this->search}%")
                    ->orWhere('customer_data->email', 'like', "%{$this->search}%")
                    ->orWhere('la_geria_order_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return [
            'bundles' => $query->paginate(15),
            'laGeriaProducts' => $this->laGeriaProducts(),
            'lanzaloeProducts' => $this->lanzaloeProducts(),
        ];
    }

    private function laGeriaProducts(): array
    {
        return NovaExternalCatalogItem::query()
            ->where('source', 'woo')
            ->whereIn('type', ['simple', 'variable', 'booking'])
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'external_id', 'name', 'sku', 'price'])
            ->map(fn ($p) => ['id' => $p->external_id, 'name' => $p->name, 'sku' => $p->sku, 'price' => $p->price])
            ->toArray();
    }

    private function lanzaloeProducts(): array
    {
        return NovaExternalCatalogItem::query()
            ->where('source', 'magento')
            ->whereIn('type', ['simple', 'configurable'])
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'external_id', 'name', 'sku', 'price'])
            ->map(fn ($p) => ['id' => $p->sku ?: $p->external_id, 'name' => $p->name, 'sku' => $p->sku, 'price' => $p->price])
            ->toArray();
    }

    public function syncProducts(): void
    {
        $this->syncResult = [];

        try {
            $wooSetting = $this->findIntegrationSetting('woo');
            if ($wooSetting) {
                $wooSync = app(NovaWooCommerceApiSyncService::class);
                $this->syncResult['la_geria'] = $wooSync->sync($wooSetting, true);
            }
        } catch (\Throwable $e) {
            $this->syncResult['la_geria_error'] = $e->getMessage();
        }

        try {
            $magentoSetting = $this->findIntegrationSetting('magento');
            if ($magentoSetting) {
                $magentoSync = app(NovaMagentoApiSyncService::class);
                $this->syncResult['lanzaloe'] = $magentoSync->sync($magentoSetting, true);
            }
        } catch (\Throwable $e) {
            $this->syncResult['lanzaloe_error'] = $e->getMessage();
        }
    }

    private function findIntegrationSetting(string $type): ?\App\Models\NovaIntegrationSetting
    {
        return \App\Models\NovaIntegrationSetting::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    public function createBundle(): void
    {
        $this->validate([
            'form.first_name' => 'required|string|max:120',
            'form.last_name' => 'required|string|max:120',
            'form.email' => 'required|email|max:120',
            'form.phone' => 'required|string|max:50',
            'form.address' => 'required|string|max:255',
            'form.city' => 'required|string|max:120',
            'form.postcode' => 'required|string|max:20',
            'form.country' => 'required|string|max:2',
            'form.la_geria_product_id' => 'nullable',
            'form.lanzaloe_sku' => 'nullable',
        ]);

        $data = $this->form;
        $data['street'] = [$data['address']];

        $result = app(NovaBundleOrderService::class)->createBundle($data);

        if ($result['success']) {
            $this->dispatch('toast', type: 'success', title: 'Bundle creado: '.$result['bundle_reference']);
            $this->reset('form');
            $this->showCreateModal = false;
        } else {
            $this->dispatch('toast', type: 'error', title: 'Bundle parcial o fallido');
        }
    }

    public function openInvoiceModal(int $bundleId): void
    {
        $this->invoiceBundleId = $bundleId;
        $this->showInvoiceModal = true;
    }

    public function generateInvoice(): void
    {
        $this->validate(['invoiceBundleId' => 'required|integer']);

        $bundle = NovaBundleOrder::query()->findOrFail($this->invoiceBundleId);
        $customer = $bundle->customer_data;

        DB::transaction(function () use ($bundle, $customer) {
            $cliente = Cliente::query()->firstOrCreate(
                ['email' => $customer['email'] ?? null],
                [
                    'nombre' => $customer['first_name'] ?? '',
                    'apellido' => $customer['last_name'] ?? '',
                    'domicilio' => $customer['address'] ?? '',
                    'poblacion' => $customer['city'] ?? '',
                    'codigopostal' => $customer['postcode'] ?? '',
                    'pais' => $customer['country'] ?? 'ES',
                    'telefono' => $customer['phone'] ?? '',
                    'empresa_id' => 1,
                    'fechaalta' => now(),
                ]
            );

            $concepto = Concepto::query()->firstOrCreate(
                ['nombre' => 'Pedido cruzado bundle'],
                [
                    'precio' => 0,
                    'impuesto' => 7,
                    'retenciones' => 0,
                ]
            );

            $total = (float) ($bundle->la_geria_total ?? 0);
            $igic = round($total * 0.07, 2);

            $factura = new Factura;
            $factura->cliente_id = $cliente->id;
            $factura->empresa_id = $cliente->empresa_id ?: 1;
            $factura->fechaemitido = now();
            $factura->baseimponible = $total;
            $factura->impuesto = $igic;
            $factura->importe = $total + $igic;
            $factura->notas = 'Bundle '.$bundle->bundle_reference;
            $factura->observaciones = 'La Geria: '.($bundle->la_geria_order_number ?? '-').', Lanzaloe: '.($bundle->lanzaloe_order_id ?? '-');
            $factura->save();

            $factura->registros()->create([
                'concepto_id' => $concepto->id,
                'descripcion' => 'Pedido cruzado '.$bundle->bundle_reference,
                'cantidad' => 1,
                'unidad' => 'ud',
                'precio' => $total,
                'descuento' => 0,
                'impuesto' => 7,
                'retenciones' => 0,
                'valorimpuesto' => 7,
                'valorretenciones' => 0,
                'importe' => $total,
                'fecha' => now()->format('Y-m-d'),
            ]);

            $bundle->update(['factura_id' => $factura->id]);
        });

        $this->dispatch('toast', type: 'success', title: 'Factura generada.');
        $this->showInvoiceModal = false;
        $this->invoiceBundleId = null;
    }
};