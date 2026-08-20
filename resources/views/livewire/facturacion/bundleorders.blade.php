
<?php

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Factura;
use App\Models\NovaBundleOrder;
use App\Models\NovaBundleProduct;
use App\Models\NovaExternalCatalogItem;
use App\Services\Nova\NovaBundleOrderService;
use App\Services\Nova\NovaMagentoApiSyncService;
use App\Services\Nova\NovaWooCommerceApiSyncService;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.front')] class extends Component
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

    public ?int $selectedBundleProductId = null;

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
            'bundleProducts' => $this->bundleProducts(),
        ];
    }

    private function bundleProducts(): array
    {
        return NovaBundleProduct::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'la_geria_product_id', 'la_geria_quantity', 'lanzaloe_sku', 'lanzaloe_quantity', 'total_price'])
            ->toArray();
    }

    private function laGeriaProducts(): array
    {
        return NovaExternalCatalogItem::query()
            ->where('source', 'woo')
            ->whereIn('type', ['product','simple', 'variable', 'booking'])
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
            ->whereIn('type', ['product', 'configurable'])
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
            ->where('source_type', $type)
            ->where('status', 'active')
            ->first();
    }

    public function selectBundleProduct(): void
    {
        if (! $this->selectedBundleProductId) {
            return;
        }

        $product = NovaBundleProduct::query()->findOrFail($this->selectedBundleProductId);

        $this->form['la_geria_product_id'] = $product->la_geria_product_id;
        $this->form['la_geria_quantity'] = $product->la_geria_quantity;
        $this->form['lanzaloe_sku'] = $product->lanzaloe_sku;
        $this->form['lanzaloe_quantity'] = $product->lanzaloe_quantity;
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
                ['concepto' => 'Pedido cruzado bundle'],
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
?>

<div class="p-6" x-data="{ createOpen: @entangle('showCreateModal'), invoiceOpen: @entangle('showInvoiceModal') }">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Pedidos cruzados (Bundles)</h1>
        <div class="flex gap-3">
            <button wire:click="syncProducts" wire:loading.attr="disabled" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50">
                <span wire:loading.remove wire:target="syncProducts">Sincronizar productos</span>
                <span wire:loading wire:target="syncProducts">Sincronizando...</span>
            </button>
            <button @click="createOpen = true" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">
                + Nuevo bundle
            </button>
        </div>
    </div>

    @if ($syncResult)
        <div class="mb-4 p-4 bg-slate-50 rounded-lg border border-slate-200 text-sm">
            <div class="font-medium text-slate-700 mb-1">Resultado sincronización:</div>
            @isset($syncResult['la_geria'])
                <div class="text-emerald-700">La Geria: {{ $syncResult['la_geria']['products_processed'] ?? 0 }} productos procesados.</div>
            @endisset
            @isset($syncResult['la_geria_error'])
                <div class="text-red-600">La Geria error: {{ $syncResult['la_geria_error'] }}</div>
            @endisset
            @isset($syncResult['lanzaloe'])
                <div class="text-emerald-700">Lanzaloe: {{ $syncResult['lanzaloe']['products_processed'] ?? 0 }} productos procesados.</div>
            @endisset
            @isset($syncResult['lanzaloe_error'])
                <div class="text-red-600">Lanzaloe error: {{ $syncResult['lanzaloe_error'] }}</div>
            @endisset
        </div>
    @endif

    <div class="flex gap-4 mb-6">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar referencia, email o pedido La Geria..."
                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
            <select wire:model.live="status" class="rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Todos los estados</option>
                <option value="created">Creado</option>
                <option value="partial">Parcial</option>
                <option value="cancelled">Cancelado</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Referencia</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">La Geria</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Lanzaloe</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pedido</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pago</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Factura</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($bundles as $bundle)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $bundle->bundle_reference }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ data_get($bundle->customer_data, 'first_name') }} {{ data_get($bundle->customer_data, 'last_name') }}<br>
                            <span class="text-xs text-slate-400">{{ data_get($bundle->customer_data, 'email') }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            @if ($bundle->la_geria_order_id)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                    #{{ $bundle->la_geria_order_number }}
                                </span>
                                <div class="text-xs text-slate-400 mt-1">{{ $bundle->la_geria_status }}</div>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            @if ($bundle->lanzaloe_order_id)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">
                                    #{{ $bundle->lanzaloe_order_id }}
                                </span>
                            @elseif ($bundle->lanzaloe_cart_id)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700">
                                    Carrito {{ $bundle->lanzaloe_cart_id }}
                                </span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                            @if ($bundle->lanzaloe_error)
                                <div class="text-xs text-red-500 mt-1 truncate max-w-[200px]" title="{{ $bundle->lanzaloe_error }}">{{ $bundle->lanzaloe_error }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span @class([
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-800' => $bundle->status === 'created',
                                'bg-amber-100 text-amber-800' => $bundle->status === 'partial',
                                'bg-slate-100 text-slate-800' => $bundle->status === 'cancelled',
                            ])>
                                {{ $bundle->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span @class([
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-800' => $bundle->payment_status === 'paid',
                                'bg-amber-100 text-amber-800' => $bundle->payment_status === 'pending',
                                'bg-red-100 text-red-800' => $bundle->payment_status === 'failed',
                            ])>
                                {{ $bundle->payment_status ?? 'pending' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            @if ($bundle->factura_id)
                                <a href="{{ route('facturacion.facturas') }}?search={{ $bundle->factura->codfactura }}" class="text-emerald-600 hover:underline">{{ $bundle->factura->codfactura }}</a>
                            @else
                                <button wire:click="openInvoiceModal({{ $bundle->id }})" class="text-xs text-emerald-600 hover:underline">Generar factura</button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $bundle->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('public.bundle') }}?ref={{ $bundle->bundle_reference }}" target="_blank" class="text-slate-400 hover:text-slate-600 mr-3">Ver</a>
                            @if ($bundle->payment_status !== 'paid')
                                <a href="{{ route('bundle.redsys.start', $bundle) }}" target="_blank" class="text-emerald-600 hover:underline">Pagar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">No hay pedidos cruzados registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $bundles->links() }}
    </div>

    {{-- Create bundle modal --}}
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.away="createOpen = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-slate-900">Nuevo pedido cruzado</h2>
                <button @click="createOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            @if (count($bundleProducts) > 0)
                <div class="col-span-2 mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Producto bundle predefinido</label>
                    <select wire:model.live="selectedBundleProductId" wire:change="selectBundleProduct" class="w-full rounded-lg border-slate-300">
                        <option value="">Personalizado (sin plantilla)</option>
                        @foreach ($bundleProducts as $product)
                            <option value="{{ $product['id'] }}">{{ $product['name'] }} — {{ number_format($product['total_price'], 2) }}€</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                    <input wire:model="form.first_name" type="text" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Apellidos</label>
                    <input wire:model="form.last_name" type="text" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input wire:model="form.email" type="email" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                    <input wire:model="form.phone" type="text" class="w-full rounded-lg border-slate-300">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                    <input wire:model="form.address" type="text" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ciudad</label>
                    <input wire:model="form.city" type="text" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">CP</label>
                    <input wire:model="form.postcode" type="text" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">País</label>
                    <input wire:model="form.country" type="text" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Empresa</label>
                    <input wire:model="form.company" type="text" class="w-full rounded-lg border-slate-300">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <h3 class="font-medium text-slate-900 mb-2">La Geria</h3>
                    <label class="block text-sm text-slate-600 mb-1">Producto</label>
                    <select wire:model="form.la_geria_product_id" class="w-full rounded-lg border-slate-300 mb-2">
                        <option value="">Selecciona...</option>
                        @foreach ($laGeriaProducts as $product)
                            <option value="{{ $product['id'] }}">{{ $product['name'] }} ({{ $product['price'] }}€)</option>
                        @endforeach
                    </select>
                    <label class="block text-sm text-slate-600 mb-1">Cantidad</label>
                    <input wire:model="form.la_geria_quantity" type="number" min="1" class="w-full rounded-lg border-slate-300">
                </div>
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <h3 class="font-medium text-slate-900 mb-2">Lanzaloe</h3>
                    <label class="block text-sm text-slate-600 mb-1">Producto</label>
                    <select wire:model="form.lanzaloe_sku" class="w-full rounded-lg border-slate-300 mb-2">
                        <option value="">Selecciona...</option>
                        @foreach ($lanzaloeProducts as $product)
                            <option value="{{ $product['id'] }}">{{ $product['name'] }} ({{ $product['price'] }}€)</option>
                        @endforeach
                    </select>
                    <label class="block text-sm text-slate-600 mb-1">Cantidad</label>
                    <input wire:model="form.lanzaloe_quantity" type="number" min="1" class="w-full rounded-lg border-slate-300">
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button @click="createOpen = false" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</button>
                <button wire:click="createBundle" wire:loading.attr="disabled" class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700">
                    <span wire:loading.remove wire:target="createBundle">Crear bundle</span>
                    <span wire:loading wire:target="createBundle">Creando...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Generate invoice modal --}}
    <div x-show="invoiceOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.away="invoiceOpen = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h2 class="text-xl font-bold text-slate-900 mb-4">Generar factura</h2>
            <p class="text-sm text-slate-600 mb-6">Se creará una factura en NovaFact vinculada a este bundle y se buscará/creará el cliente por email.</p>
            <div class="flex justify-end gap-3">
                <button @click="invoiceOpen = false" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</button>
                <button wire:click="generateInvoice" wire:loading.attr="disabled" class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700">
                    <span wire:loading.remove wire:target="generateInvoice">Generar</span>
                    <span wire:loading wire:target="generateInvoice">Generando...</span>
                </button>
            </div>
        </div>
    </div>
</div>