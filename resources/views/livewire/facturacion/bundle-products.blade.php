
<?php

use App\Models\NovaBundleProduct;
use App\Models\NovaExternalCatalogItem;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.front')] class extends Component
{
    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '',
        'description' => '',
        'status' => true,
        'la_geria_product_id' => '',
        'la_geria_quantity' => 1,
        'lanzaloe_sku' => '',
        'lanzaloe_quantity' => 1,
        'total_price' => '',
    ];

    public function with(): array
    {
        $query = NovaBundleProduct::query()->orderBy('name');

        if ($this->search !== '') {
            $query->where('name', 'like', "%{$this->search}%");
        }

        return [
            'products' => $query->paginate(20),
            'laGeriaProducts' => $this->laGeriaProducts(),
            'lanzaloeProducts' => $this->lanzaloeProducts(),
        ];
    }

    private function laGeriaProducts(): array
    {
        return NovaExternalCatalogItem::query()
            ->where('source', 'woo')
            ->whereIn('type', ['product', 'simple', 'variable', 'booking'])
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'external_id', 'name', 'sku', 'price'])
            ->map(fn ($p) => ['id' => $p->external_id, 'name' => $p->name, 'sku' => $p->sku, 'price' => $p->price])
            ->toArray();
    }

    private function lanzaloeProducts(): array
    {
        return NovaExternalCatalogItem::query()
            ->where('source', 'magento')
            ->whereIn('type', ['product', 'simple', 'configurable'])
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'external_id', 'name', 'sku', 'price'])
            ->map(fn ($p) => ['id' => $p->sku ?: $p->external_id, 'name' => $p->name, 'sku' => $p->sku, 'price' => $p->price])
            ->toArray();
    }

    public function openModal(?int $id = null): void
    {
        $this->editingId = $id;
        $this->reset('form');

        if ($id) {
            $product = NovaBundleProduct::query()->findOrFail($id);
            $this->form = [
                'name' => $product->name,
                'description' => $product->description ?? '',
                'status' => (bool) $product->status,
                'la_geria_product_id' => $product->la_geria_product_id,
                'la_geria_quantity' => $product->la_geria_quantity,
                'lanzaloe_sku' => $product->lanzaloe_sku,
                'lanzaloe_quantity' => $product->lanzaloe_quantity,
                'total_price' => (string) $product->total_price,
            ];
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.description' => 'nullable|string',
            'form.la_geria_product_id' => 'required|string|max:120',
            'form.la_geria_quantity' => 'required|integer|min:1',
            'form.lanzaloe_sku' => 'required|string|max:120',
            'form.lanzaloe_quantity' => 'required|integer|min:1',
            'form.total_price' => 'required|numeric|min:0',
        ]);

        $laGeria = collect($this->laGeriaProducts())->firstWhere('id', $this->form['la_geria_product_id']);
        $lanzaloe = collect($this->lanzaloeProducts())->firstWhere('id', $this->form['lanzaloe_sku']);

        $payload = [
            'name' => $this->form['name'],
            'description' => $this->form['description'],
            'status' => (bool) $this->form['status'],
            'la_geria_product_id' => $this->form['la_geria_product_id'],
            'la_geria_product_name' => $laGeria['name'] ?? '',
            'la_geria_quantity' => (int) $this->form['la_geria_quantity'],
            'la_geria_unit_price' => $laGeria['price'] ?? 0,
            'lanzaloe_sku' => $this->form['lanzaloe_sku'],
            'lanzaloe_product_name' => $lanzaloe['name'] ?? '',
            'lanzaloe_quantity' => (int) $this->form['lanzaloe_quantity'],
            'lanzaloe_unit_price' => $lanzaloe['price'] ?? 0,
            'total_price' => (float) $this->form['total_price'],
        ];

        NovaBundleProduct::query()->updateOrCreate(
            ['id' => $this->editingId],
            $payload
        );

        $this->showModal = false;
        $this->dispatch('toast', type: 'success', title: 'Producto bundle guardado.');
    }

    public function toggleStatus(int $id): void
    {
        $product = NovaBundleProduct::query()->findOrFail($id);
        $product->update(['status' => ! $product->status]);
    }
};
?>
<div x-data="selectPrimary('bundles')">

<div class="p-6" x-data="{ open: @entangle('showModal') }">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Productos Bundle</h1>
        <button @click="open = true" wire:click="openModal" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">
            + Nuevo producto bundle
        </button>
    </div>

    <div class="flex gap-4 mb-6">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar producto bundle..."
                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Nombre</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">La Geria</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Lanzaloe</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">PVP</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Activo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($products as $product)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $product->la_geria_product_name }} x{{ $product->la_geria_quantity }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $product->lanzaloe_product_name }} x{{ $product->lanzaloe_quantity }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900 font-medium">{{ number_format($product->total_price, 2) }}€</td>
                        <td class="px-4 py-3 text-sm">
                            <button wire:click="toggleStatus({{ $product->id }})" @class([
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-800' => $product->status,
                                'bg-slate-100 text-slate-800' => ! $product->status,
                            ])>
                                {{ $product->status ? 'Activo' : 'Inactivo' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <button wire:click="openModal({{ $product->id }})" @click="open = true" class="text-emerald-600 hover:underline mr-3">Editar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No hay productos bundle configurados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.away="open = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-slate-900">{{ $editingId ? 'Editar' : 'Nuevo' }} producto bundle</h2>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del bundle</label>
                    <input wire:model="form.name" type="text" class="w-full rounded-lg border-slate-300">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                    <textarea wire:model="form.description" rows="2" class="w-full rounded-lg border-slate-300"></textarea>
                </div>

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

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">PVP total (€)</label>
                    <input wire:model="form.total_price" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300">
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="form.status" type="checkbox" id="status" class="rounded border-slate-300 text-emerald-600">
                    <label for="status" class="text-sm text-slate-700">Activo</label>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button @click="open = false" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</button>
                <button wire:click="save" wire:loading.attr="disabled" class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700">
                    <span wire:loading.remove wire:target="save">Guardar</span>
                    <span wire:loading wire:target="save">Guardando...</span>
                </button>
            </div>
        </div>
    </div>
</div>
</div>