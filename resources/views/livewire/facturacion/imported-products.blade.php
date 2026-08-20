
<?php

use App\Models\NovaExternalCatalogItem;
use App\Services\Nova\NovaMagentoApiSyncService;
use App\Services\Nova\NovaWooCommerceApiSyncService;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.front')] class extends Component
{
    public string $search = '';

    public string $source = '';

    public array $syncResult = [];

    public function with(): array
    {
        $query = NovaExternalCatalogItem::query()->orderBy('name');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%")
                    ->orWhere('external_id', 'like', "%{$this->search}%");
            });
        }

        if ($this->source !== '') {
            $query->where('source', $this->source);
        }

        return [
            'products' => $query->paginate(25),
        ];
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
};
?>
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Catálogo importado</h1>
        <button wire:click="syncProducts" wire:loading.attr="disabled" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">
            <span wire:loading.remove wire:target="syncProducts">Sincronizar todo</span>
            <span wire:loading wire:target="syncProducts">Sincronizando...</span>
        </button>
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
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar nombre, SKU o ID..."
                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
            <select wire:model.live="source" class="rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Todos los orígenes</option>
                <option value="woo">La Geria (WooCommerce)</option>
                <option value="magento">Lanzaloe (Magento)</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Nombre</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Origen</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Precio</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Stock</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Última sinc.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($products as $product)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                'bg-blue-50 text-blue-700' => $product->source === 'woo',
                                'bg-emerald-50 text-emerald-700' => $product->source === 'magento',
                            ])>
                                {{ $product->source === 'woo' ? 'La Geria' : 'Lanzaloe' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 font-mono">{{ $product->sku ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $product->type ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900">{{ $product->price ? number_format($product->price, 2).'€' : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $product->stock_status ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $product->last_synced_at?->format('d/m/Y H:i') ?? $product->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No hay productos importados. Pulsa "Sincronizar todo".</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
