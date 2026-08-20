<?php

use App\Actions\CreateGastoFromReceiptAction;
use App\Actions\ExtractReceiptData;
use App\Models\Category;
use App\Models\Cliente;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Gasto;
use Livewire\WithFileUploads;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Attributes\Layout;

new #[Title('Gastos')] #[Layout('layouts.front')] class extends Component
{
    use WithFileUploads;

    public $receiptImage = null;

    public ?string $ocrEmpresa = null;

    public ?int $proveedor_id = null;

    public string $search = '';
    public ?int $filterCategory = null;
    public ?int $filterProveedor = null;
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortBy = 'fecha';
    public string $sortDir = 'desc';

    public bool $showModal = false;
    public bool $showModalCategory = false;
    public bool $showCambiarPoveedorModal = false;

    public string $mode = 'create';
    public ?int $editId = null;

    public array $clientes = [];

    public ?int $deleteExpenseId = null;

    public array $deleteExpenseIds = [];
    public ?int $updateExpenseId = null;

    public array $updateExpenseIds = [];
    public array $selectedExpenses = [];

    public bool $selectAll = false;

    #[Validate('required|string|max:255')]
    public string $description = '';

    #[Validate('string|max:255')]
    public string $codigo = '';
    #[Validate('required|numeric|min:0.01')]
    public string $amount = '';

    #[Validate('nullable|exists:categories,id')]
    public ?int $category_id = null;

    #[Validate('required|date')]
    public string $date = '';

    public string $frequency = 'monthly';

    public string $recurringEndDate = '';
    public bool $showDeleteModal = false;

        public bool $showModalRecurrency = false;

        public bool $showCambiarTipoModal = false;

    public string $name = '';

    public string $type = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->clientes =  Cliente::all()->pluck('nombretotal', 'id')->toArray();

    }

        public function selectExpense(int $id): void
    {
        if (isset($this->selectedExpenses[$id])) {
            $this->selectedExpenses[$id] = false;

        } else {
            $this->selectedExpenses[$id] = true;
        }
    }

    public function toggleSelectAll(): void
    {
        $expenses = $this->getExpenses();
        $ids = $expenses->pluck('id')->all();

        if ($this->selectAll) {
            foreach ($ids as $id) {
                $this->selectedExpenses[$id] = true;
            }
            return;
        }

        $this->selectedExpenses = [];
    }
        #[Computed]
    public function getExpenses(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Gasto::query()
            ->where('type', 'expense')
            ->when($this->search, fn (Builder $q) => $q->where('descripcion', 'like', '%'.$this->search.'%'))
            ->when($this->filterCategory, fn (Builder $q) => $q->where('category_id', $this->filterCategory))
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('fecha', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $q) => $q->whereDate('fecha', '<=', $this->dateTo))
            ->with(['category', 'proveedor'])
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(15);
    }
    #[Computed]
    public function expenses(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Gasto::query()
            ->where('type', 'expense')
            ->when($this->search, fn (Builder $q) => $q->where('descripcion', 'like', '%'.$this->search.'%'))
            ->when($this->filterCategory, fn (Builder $q) => $q->where('category_id', $this->filterCategory))
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('fecha', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $q) => $q->whereDate('fecha', '<=', $this->dateTo))
            ->with(['category', 'proveedor'])
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(15);
    }
    private function mesEnEspanol(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $fecha = filled($this->date)
            ? Carbon::parse($this->date)
            : now();

        return $meses[$fecha->month - 1];
    }
    #[Computed]
    public function categories(): array
    {
        return Category::query()
            ->where(fn (Builder $q) => $q->where('type', 'expense')->orWhere('type', 'both')->orWhereNull('type'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
public function openCategorias(){
route('facturacion.ajustes');
}
    #[Computed]
    public function stats(): array
    {
        $query = Gasto::query()->where('type', 'expense')
            ->when($this->filterCategory, fn (Builder $q) => $q->where('category_id', $this->filterCategory))
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('fecha', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $q) => $q->whereDate('fecha', '<=', $this->dateTo));

        $total = (float) $query->sum('total');
        $count = $query->count();

        $thisMonth = Gasto::query()
            ->where('type', 'expense')
            ->whereYear('fecha', now()->year)
            ->whereMonth('fecha', now()->month)
            ->sum('total');

        return [
            'count' => $count,
            'total' => $total,
            'average' => $count > 0 ? round($total / $count, 2) : 0,
            'thisMonth' => (float) $thisMonth,
        ];
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }

        unset($this->expenses);
    }
    public function openCreateCategory(): void
    {
        $this->resetFormCategory();
        $this->mode = 'create';
        $this->showModalCategory = true;
        $this->showModal = false;

    }
        public function openCreate(): void
    {
        $this->resetForm();
        $this->mode = 'create';
        $this->showModal = true;
        $this->showModalCategory = false;

    }
        public function openCreateRecurrency(): void
    {
        $this->resetForm();
        $this->mode = 'create';
        $this->showModal = false;
        $this->showModalCategory = false;
        $this->showModalRecurrency = true;

    }


    public function openEdit(int $id): void
    {
        $expense = Gasto::query()->where('type', 'expense')->findOrFail($id);

        $this->editId = $expense->id;
        $this->description = $expense->description;
        $this->amount = (string) $expense->amount;
        $this->category_id = $expense->category_id;
        $this->proveedor_id = $expense->proveedor_id;
        $this->codigo = $expense->codigo;

        $this->date = $expense->date?->format('Y-m-d') ?? now()->format('Y-m-d');

        $this->mode = 'edit';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $expense = new Gasto([
            'description' => $this->description,
            'total' => $this->amount,
            'codigo' => $this->codigo,
            'category_id' => $this->category_id,
            'fecha' => $this->date,
            'proveedor_id' => $this->proveedor_id,
        ]);
        $expense->user_id = auth()->id();
        $expense->empresa_id = $this->proveedor_id
            ? Cliente::find($this->proveedor_id)?->empresa_id
            : null;
        $expense->documento = $this->storeReceiptImage();
        $expense->save();

        $this->resetForm();
        $this->showModal = false;
        unset($this->expenses, $this->stats);

        $this->dispatch('toast', ['type' => 'success', 'description' => 'Gasto añadido correctamente']);
    }
    public function saveRecurring(): void
    {
        $this->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'date' => 'required|date',
            'recurringEndDate' => 'nullable|date|after:date',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $recurring = new RecurringTransaction([
            'name' => $this->description,
            'amount' => $this->amount,
            'type' => 'expense',
            'frequency' => $this->frequency,
            'category_id' => $this->category_id ?: null,
            'start_date' => $this->date,
            'next_due_date' => $this->date,
            'end_date' => $this->recurringEndDate ?: null,
            'is_active' => true,
        ]);
        $recurring->user_id = auth()->id();
        $recurring->save();

        // Generar los gastos desde la fecha de inicio hasta la fecha final (o hasta hoy si no hay fecha final)
        $cursor = Carbon::parse($this->date);
        $limit = $this->recurringEndDate ? Carbon::parse($this->recurringEndDate) : now();
        $created = 0;

        while ($cursor->lte($limit)) {
            $gasto = new Gasto([
                'descripcion' => $this->description,
                'total' => $this->amount,
                'type' => 'expense',
                'category_id' => $this->category_id ?: null,
                'fecha' => $cursor->toDateString(),
            ]);
            $gasto->user_id = auth()->id();
            $gasto->save();
            $created++;

            $cursor = match ($this->frequency) {
                'daily' => $cursor->addDay(),
                'weekly' => $cursor->addWeek(),
                'monthly' => $cursor->addMonth(),
                'yearly' => $cursor->addYear(),
            };
        }

        $recurring->update([
            'next_due_date' => $cursor->toDateString(),
            'is_active' => $this->recurringEndDate === '' || $cursor->lte(Carbon::parse($this->recurringEndDate)),
        ]);

        $this->resetForm();
        $this->recurringEndDate = '';
        $this->frequency = 'monthly';
        $this->showModal = false;
        unset($this->expenses, $this->stats);

        $this->dispatch('toast', ['type' => 'success', 'description' => "Gasto recurrente creado: {$created} gastos generados"]);
    }

    public function saveCategory(): void
    {
        $this->validate();

        $category = new Category([
            'name' => $this->name,
            'type' => $this->type,
        ]);
        $category->user_id = auth()->id();
        $category->save();

        $this->resetFormCategory();
        $this->showModalCategory = false;

        unset($this->category, $this->stats);

        $this->dispatch('toast', ['type' => 'success', 'description' => 'Categoría creada correctamente']);
    }

    public function update(): void
    {
        $this->validate();

        $expense = Gasto::query()->where('type', 'expense')->findOrFail($this->editId);

        $expense->update([
            'descripcion' => $this->description,
            'codigo' => $this->codigo,
            'total' => $this->amount,
            'category_id' => $this->category_id,
            'fecha' => $this->date,
            'proveedor_id' => $this->proveedor_id ?? $expense->proveedor_id,
            'documento' => $this->storeReceiptImage() ?? $expense->documento,
        ]);

        $this->resetForm();
        $this->showModal = false;
        unset($this->expenses, $this->stats);

        $this->dispatch('toast', ['type' => 'success', 'description' => 'Gasto actualizado']);
    }

    public function delete(int $id): void
    {

        Gasto::query()->where('type', 'expense')->findOrFail($id)->delete();
        unset($this->expenses, $this->stats);
        $this->showModal = false;

        $this->dispatch('toast', ['type' => 'success', 'description' => 'Gasto eliminado']);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterCategory = null;
        $this->filterProveedor = null;
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }
public function alertSelected(){
            $ids = array_keys(array_filter($this->selectedExpenses));

$this->dispatch('toast', type: 'success', title: 'Gastos(s) exportados(s). ');


}
    public function exportCsv(): StreamedResponse
    {
$this->dispatch('toast', type: 'success', title: 'Gastos(s) exportados(s).');

        $expenses = Gasto::query()
            ->where('type', 'expense')
            ->when($this->search, fn (Builder $q) => $q->where('descripcion', 'like', '%'.$this->search.'%'))
            ->when($this->filterCategory, fn (Builder $q) => $q->where('category_id', $this->filterCategory))
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('fecha', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $q) => $q->whereDate('fecha', '<=', $this->dateTo))
            ->orderBy('fecha', 'desc')
            ->get();

        $filename = 'gastos-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Descripción', 'Categoría', 'Importe']);

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->date?->format('d/m/Y') ?? '',
                    $expense->description,
                    $expense->category?->name ?? '—',
                    number_format($expense->amount, 2, ',', '.'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

        private function resetFormCategory(): void
    {
        $this->reset(['name', 'type']);
        $this->resetValidation();
    }
    public function confirmDeleteSelected(): void
    {
        $ids = array_keys(array_filter($this->selectedExpenses));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'warning', title: 'Selecciona al menos un gasto.');
            return;
        }

        $this->deleteExpenseIds = $ids;
        $this->deleteExpenseId = null;
        $this->showDeleteModal = true;
    }

       public function ejecutarDeleteExpense(): void
    {
        $anos = [];

        if ($this->deleteExpenseId) {
            $expenses = Gasto::findOrFail($this->deleteExpenseId);
            $expenses->delete();
        } elseif (! empty($this->deleteExpenseIds)) {
            $expenses = Gasto::query()->whereIn('id', $this->deleteExpenseIds)->get();
            Gasto::query()->whereIn('id', $this->deleteExpenseIds)->delete();
            $this->selectedExpenses = [];
        } else {
            return;
        }

        $this->showDeleteModal = false;
        $this->deleteExpenseId = null;
        $this->deleteExpenseIds = [];
        $this->dispatch('toast', type: 'success', title: 'Gastos(s) eliminado(s).');
    }

        public function confirmUpdateSelected(): void
    {
        $ids = array_keys(array_filter($this->selectedExpenses));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'warning', title: 'Selecciona al menos un gasto.');
            return;
        }

        $this->updateExpenseIds = $ids;
        $this->updateExpenseId = null;
        $this->showCambiarTipoModal = true;
    }

        public function confirmUpdateProveedorSelected(): void
    {
        $ids = array_keys(array_filter($this->selectedExpenses));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'warning', title: 'Selecciona al menos un gasto.');
            return;
        }

        $this->updateExpenseIds = $ids;
        $this->updateExpenseId = null;
        $this->showCambiarPoveedorModal = true;
    }
     public function ejecutarCambiarProveedor(): void
    {
        $anos = [];

        if ($this->updateExpenseId) {
            $expenses = Gasto::findOrFail($this->updateExpenseId);

            $expenses->update([
                'proveedor_id' => $this->proveedor_id,
            ]);

        } elseif (! empty($this->updateExpenseIds)) {
            $expenses = Gasto::query()->whereIn('id', $this->updateExpenseIds)->get();
            Gasto::query()->whereIn('id', $this->updateExpenseIds)->update([
                'proveedor_id' => $this->proveedor_id,
            ]);
            $this->selectedExpenses = [];
        } else {
            return;
        }

        $this->showCambiarPoveedorModal = false;
        $this->updateExpenseId = null;
        $this->updateExpenseIds = [];
        $this->dispatch('toast', type: 'success', title: 'Gastos(s) actualizados(s).');
    }
        public function ejecutarCambiarTipo(): void
    {
        $anos = [];

        if ($this->updateExpenseId) {
            $expenses = Gasto::findOrFail($this->updateExpenseId);

            $expenses->update([
                'category_id' => $this->category_id,
            ]);

        } elseif (! empty($this->updateExpenseIds)) {
            $expenses = Gasto::query()->whereIn('id', $this->updateExpenseIds)->get();
            Gasto::query()->whereIn('id', $this->updateExpenseIds)->update([
                'category_id' => $this->category_id,
            ]);
            $this->selectedExpenses = [];
        } else {
            return;
        }

        $this->showCambiarTipoModal = false;
        $this->updateExpenseId = null;
        $this->updateExpenseIds = [];
        $this->dispatch('toast', type: 'success', title: 'Gastos(s) actualizados(s).');
    }
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteFacturaId = null;
        $this->deleteFacturaIds = [];
        $this->ajustarContador = false;
    }
    public function deleteSelected(): void
    {
        $ids = array_keys(array_filter($this->selectedExpenses));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'warning', title: 'Selecciona al menos un gasto.');
            return;
        }

        Gasto::query()->whereIn('id', $ids)->delete();
        $this->selectedExpenses = [];
        $this->dispatch('toast', type: 'success', title: count($ids).' gastos(s) eliminado(s).');
    }
    public function selectCategoria(int $categoriaId): void
    {
    $categoria = Category::findOrFail($categoriaId);
    $nombre = $categoria->name;
    $this->description = $nombre . " - " . $this->date;

    }

    public function updatedReceiptImage(): void
    {
        $this->validate([
            'receiptImage' => 'image|max:10240',
        ]);

        try {
            $response = app(ExtractReceiptData::class)->handle(
                $this->receiptImage->getRealPath(),
                $this->receiptImage->getMimeType(),
            );

            $empresa = $response['empresa'] ?? null;
            $concepto = $response['concepto'] ?? null;


            $this->ocrEmpresa = $empresa;
            $this->proveedor_id = $empresa ? $this->resolveProveedor($empresa) : null;
            $this->description = trim(implode(' - ', array_filter([$empresa, $concepto])));

            if (! empty($response['total'])) {
                $this->amount = (string) $response['total'];
            }

            if (! empty($response['fecha'])) {
                $this->date = $response['fecha'];
            }

            $this->dispatch('toast', type: 'success', description: 'Datos extraídos del ticket. Revisa y guarda.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', description: 'No se pudieron extraer los datos de la imagen.');
        }
    }

    private function resolveProveedor(string $nombre): int
    {
        return app(CreateGastoFromReceiptAction::class)->resolveProveedor($nombre)->id;
    }

    private function storeReceiptImage(): ?string
    {
        if (! $this->receiptImage) {
            return null;
        }

        return $this->receiptImage->store('gastos/tickets', 'public');
    }

    private function resetForm(): void
    {
        $this->reset(['description', 'amount', 'category_id', 'date', 'editId', 'receiptImage', 'ocrEmpresa', 'proveedor_id','codigo']);
        $this->resetValidation();
        $this->date = now()->format('Y-m-d');
    }
} ?>

<div x-data="{secondaryOpen:false, showFilters: false,
     ctaOpen: false,
     compact: false,
     lastScrollY: 0,
     open: false,
    toggle() { this.open = ! this.open },
        activeLink: null,
        openedLinks: ['Facturas'],
        openedGroups: ['Principal', 'Informes'],
        secondaryOpen: false,
        loading: false,
        showFilters: false}"
>
    <header class="bg-background sticky top-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b px-4 lg:px-6">
        <x-ui.separator orientation="vertical" class="mr-2 data-[orientation=vertical]:h-4" />
        <h1 class="text-base font-medium">Gastos</h1>
        <div class="ml-auto flex items-center gap-2">
            <x-ui.button variant="outline" size="sm" @click="showFilters = !showFilters"
                         ::class="showFilters ? 'bg-accent' : ''">
                <x-lucide-filter class="size-4" />
                Filtrar
                @if($search || $filterCategory || $dateFrom || $dateTo || $filterPreveedor)
                    <x-ui.badge class="ml-1 size-4 p-0 flex items-center justify-center text-[9px]">!</x-ui.badge>
                @endif
            </x-ui.button>
            @if(count(array_filter($selectedExpenses)) > 0)
                <x-ui.button size="sm" variant="outline" class="text-destructive border-destructive hover:bg-destructive/10"
                             wire:click="confirmDeleteSelected">
                    <x-lucide-trash-2 class="size-4" />
                    Eliminar ({{ count(array_filter($selectedExpenses)) }})
                </x-ui.button>
                <x-ui.button size="sm" variant="outline" wire:click="confirmUpdateSelected">
                    <x-lucide-download class="size-4" />
                    Cambiar Categoría ({{ count(array_filter($selectedExpenses)) }})
                </x-ui.button>
                <x-ui.button size="sm" variant="outline" wire:click="confirmUpdateProveedorSelected">
                    <x-lucide-download class="size-4" />
                    Cambiar Proveedor ({{ count(array_filter($selectedExpenses)) }})
                </x-ui.button>
                <x-ui.button size="sm" variant="outline" wire:click="alertSelected">
                    <x-lucide-download class="size-4" />
                    Alerta ({{ count(array_filter($selectedExpenses)) }})
                </x-ui.button>
                <x-ui.button size="sm" variant="outline" wire:click="exportCsv">
                    <x-lucide-download class="size-4" />
                    Exportar CSV
                </x-ui.button>
            @endif
            <x-ui.button size="sm"  variant="outline"  href="{{ route('facturacion.ajustes') }}" >
                <x-lucide-plus class="size-4" />
                Categorías
            </x-ui.button>

            <x-ui.button size="sm" @click="$wire.openCreate()">
                <x-lucide-plus class="size-4" />
                Nuevo Gasto
            </x-ui.button>
        </div>
    </header>

    {{-- MODAL CONFIRMACIÓN ELIMINAR FACTURA --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showModalRecurrency"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">

            <div class="space-y-4 text-xs">
            </div>

        </div>
    </div>
    {{-- MODAL CONFIRMACIÓN ELIMINAR FACTURA --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showDeleteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-destructive/10 text-destructive rounded-lg">
                        <x-lucide-trash-2 class="size-4" />
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Eliminar factura</h2>
                </div>
                <button wire:click="closeDeleteModal" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <x-lucide-x class="size-4" />
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <p class="text-slate-500">¿Estás seguro de que quieres eliminar el/los gastos seleccionados?</p>

            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="closeDeleteModal" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="ejecutarDeleteExpense" type="button" class="rounded-xl bg-destructive hover:bg-destructive/90 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-destructive/10 active:scale-95 transition-all cursor-pointer">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
    {{-- MODAL CONFIRMACIÓN ELIMINAR FACTURA --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showCambiarTipoModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-destructive/10 text-destructive rounded-lg">
                        <x-lucide-trash-2 class="size-4" />
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Eliminar factura</h2>
                </div>
                <button wire:click="closeDeleteModal" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <x-lucide-x class="size-4" />
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <x-ui.field>
                    <x-ui.label>Categoría</x-ui.label>
                    <x-ui.select native wire:model="category_id" class="w-full"
                                 x-on:change="$wire.selectCategoria($event.target.value)"
                    >
                        <option value="">Sin categoría</option>
                        @foreach($this->categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.field-error :messages="$errors->get('category_id')" />
                </x-ui.field>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="closeDeleteModal" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="ejecutarCambiarTipo" type="button" class="rounded-xl bg-destructive hover:bg-destructive/90 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-destructive/10 active:scale-95 transition-all cursor-pointer">
                    Cambiar Categoría
                </button>
            </div>
        </div>
    </div>

    <div
        x-data
        x-cloak
        x-show="$wire.showCambiarPoveedorModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-destructive/10 text-destructive rounded-lg">
                        <x-lucide-trash-2 class="size-4" />
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Cambiar proveedor</h2>
                </div>
                <button wire:click="closeDeleteModal" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <x-lucide-x class="size-4" />
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <x-ui.field>
                    <x-ui.label>Proveedor</x-ui.label>
                    <x-ui.select native wire:model="proveedor_id" class="w-full"
                    >
                        <option value="">Sin proveedor</option>
                        @foreach($this->clientes as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="closeDeleteModal" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="ejecutarCambiarProveedor" type="button" class="rounded-xl bg-destructive hover:bg-destructive/90 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-destructive/10 active:scale-95 transition-all cursor-pointer">
                    Cambiar Proveedor
                </button>
            </div>
        </div>
    </div>
    <div class="flex flex-1 flex-col gap-4 p-4 md:gap-6 md:p-6 max-w-7xl w-full" style="margin: auto;">
        {{-- Stats --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-4 p-5">
                    <div class="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-xl">
                        <x-lucide-receipt class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ $this->stats['count'] }}</div>
                        <div class="text-muted-foreground text-sm">Gastos registrados</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-4 p-5">
                    <div class="bg-rose-500/10 text-rose-500 flex size-11 shrink-0 items-center justify-center rounded-xl">
                        <x-lucide-trending-up class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ number_format($this->stats['total'], 2, ',', '.') }} €</div>
                        <div class="text-muted-foreground text-sm">Total periodo</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-4 p-5">
                    <div class="bg-amber-500/10 text-amber-500 flex size-11 shrink-0 items-center justify-center rounded-xl">
                        <x-lucide-calculator class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ number_format($this->stats['average'], 2, ',', '.') }} €</div>
                        <div class="text-muted-foreground text-sm">Gasto medio</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-4 p-5">
                    <div class="bg-emerald-500/10 text-emerald-500 flex size-11 shrink-0 items-center justify-center rounded-xl">
                        <x-lucide-calendar class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ number_format($this->stats['thisMonth'], 2, ',', '.') }} €</div>
                        <div class="text-muted-foreground text-sm">Este mes</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <div x-show="showFilters" x-transition x-cloak
             class="border-b bg-muted/0 px-4 py-3 lg:px-6">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Buscar</label>
                    <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Descripción...">
                        <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
                    </x-ui.input>
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Desde</label>
                    <x-ui.input type="date" size="sm" wire:model.live="dateFrom" />
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Hasta</label>
                    <x-ui.input type="date" size="sm" wire:model.live="dateTo" />
                </div>
                <div class="w-48">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Categoría</label>
                    <x-ui.select native size="sm" wire:model.live="filterCategory" class="w-full">
                        <option value="">Todas las categorías</option>
                        @foreach($this->categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="w-48">
                    <x-ui.label>Proveedor</x-ui.label>
                    <x-ui.select native size="sm" wire:model.live="filterProveedor" class="w-full">

                        <option value="">Sin proveedor</option>
                        @foreach($this->clientes as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <x-ui.button size="sm" @click="$wire.openCreate()">
                    <x-lucide-plus class="size-4" />
                    Nuevo Gasto
                </x-ui.button>
                @if($search || $filterCategory || $dateFrom || $dateTo || $filterProveedor)
                    <x-ui.button variant="ghost" size="sm" wire:click="clearFilters" class="gap-1 text-xs text-muted-foreground">
                        <x-lucide-x class="size-3" />
                        Limpiar
                    </x-ui.button>
                @endif
            </div>
        </div>

        <x-ui.card>
            <x-ui.card-content class="p-0">
                <x-ui.table>
                    <x-ui.table-header>
                        <x-ui.table-row class="hover:bg-transparent">
                            <x-ui.table-head class="w-8 pl-4">
                                <div class="flex items-center justify-center">
                                    <input type="checkbox" class="rounded border-input size-4 cursor-pointer"
                                           wire:model.live="selectAll"
                                           @click="$wire.toggleSelectAll()">
                                </div>
                            </x-ui.table-head>
                            <x-ui.table-head>Id</x-ui.table-head>
                            <x-ui.table-head>Código</x-ui.table-head>
                            <x-ui.table-head class="cursor-pointer" wire:click="sort('fecha')">Fecha</x-ui.table-head>
                            <x-ui.table-head class="cursor-pointer" wire:click="sort('descripcion')">Descripción</x-ui.table-head>
                            <x-ui.table-head>Categoría</x-ui.table-head>
                            <x-ui.table-head>Proveedor</x-ui.table-head>
                            <x-ui.table-head class="text-right">Base</x-ui.table-head>
                            <x-ui.table-head class="text-right">Impuesto</x-ui.table-head>
                            <x-ui.table-head class="cursor-pointer text-right" wire:click="sort('total')">Total</x-ui.table-head>
                            <x-ui.table-head>Estado</x-ui.table-head>
                            <x-ui.table-head class="w-24"></x-ui.table-head>
                        </x-ui.table-row>
                    </x-ui.table-header>
                    <x-ui.table-body>
                        @php
                            //dd($this->expenses);
                        @endphp

                        @if(count($this->expenses) > 0)

                            @forelse($this->expenses as $expense)
                                <x-ui.table-row wire:key="expense-{{ $expense->id }}"
                                                @dblclick="$wire.openEdit({{ $expense->id }})"

                                                class="group border-b transition-colors hover:bg-muted/50">

                                    <x-ui.table-cell class="pl-4">
                                        <input type="checkbox" class="rounded border-input size-4 cursor-pointer"
                                               wire:click.prevent="selectExpense({{ $expense->id }})"
                                            @checked(!empty($selectedExpenses[$expense->id])) />
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="pl-4">
                                        {{ $expense->id }}
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-muted-foreground text-xs whitespace-nowrap font-mono">
                                    <span class="inline-flex items-center gap-1">
                                        {{ $expense->codigo ?? '—' }}
                                        @if($expense->documento)
                                            <a href="{{ Storage::disk('public')->url($expense->documento) }}" target="_blank"
                                               class="text-primary hover:text-primary/80" title="Ver ticket adjunto"
                                               @click.stop>
                                                <x-lucide-paperclip class="size-3.5" />
                                            </a>
                                        @endif
                                    </span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-muted-foreground text-sm whitespace-nowrap">
                                        {{ $expense->date?->format('d/m/Y') ?? '—' }}
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="font-medium text-sm">
                                        {{ $expense->description }}
                                    </x-ui.table-cell>
                                    <x-ui.table-cell>
                                        @if($expense->category)
                                            <x-ui.badge tone="neutral" size="sm">{{ $expense->category->name }}</x-ui.badge>
                                        @else
                                            <span class="text-muted-foreground text-sm">—</span>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm text-muted-foreground">
                                        {{ $expense->proveedor?->nombretotal ?? '—' }}
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-right tabular-nums text-sm text-muted-foreground">
                                        {{ number_format($expense->base_imponible, 2, ',', '.') }} €
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-right tabular-nums text-sm text-muted-foreground">
                                        {{ number_format($expense->impuesto, 2, ',', '.') }} €
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-right tabular-nums font-semibold text-sm text-rose-500">
                                        {{ number_format($expense->amount, 2, ',', '.') }} €
                                    </x-ui.table-cell>
                                    <x-ui.table-cell>
                                        <x-ui.badge size="sm" tone="{{ match($expense->estado) { 'pagado' => 'positive', 'cancelado' => 'negative', default => 'warning' } }}">
                                            {{ \App\Models\Gasto::estados()[$expense->estado] ?? $expense->estado }}
                                        </x-ui.badge>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell>
                                        <div class="ml-auto flex items-center justify-end gap-1">
                                            <x-ui.button size="sm" variant="ghost"
                                                         @click="$wire.openEdit({{ $expense->id }})"
                                                         class="size-7 p-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <x-lucide-pencil class="size-4" />
                                            </x-ui.button>
                                            <x-ui.button size="sm" variant="ghost"
                                                         wire:confirm="¿Eliminar este gasto?"
                                                         wire:click="delete({{ $expense->id }})"
                                                         class="size-7 p-0 text-destructive opacity-0 group-hover:opacity-100 transition-opacity">
                                                <x-lucide-trash-2 class="size-4" />
                                            </x-ui.button>
                                        </div>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @empty
                                <x-ui.table-row>
                                    <x-ui.table-cell colspan="10" class="py-12 text-center">
                                        <x-lucide-receipt class="size-10 text-muted-foreground mx-auto mb-3" />
                                        <p class="text-muted-foreground font-medium">No hay gastos</p>
                                        <p class="text-muted-foreground text-sm mb-4">Añade tu primer gasto para empezar.</p>
                                        <x-ui.button size="sm" @click="$wire.openCreate()">
                                            <x-lucide-plus class="size-4" />
                                            Nuevo Gasto
                                        </x-ui.button>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @endforelse
                    </x-ui.table-body>
                </x-ui.table>

                <div class="border-t px-4 py-3">
                    {{ $this->expenses->links() }}
                </div>
                @endif
            </x-ui.card-content>
        </x-ui.card>
    </div>


    {{-- Modal --}}
    <x-ui.dialog wire:model="showModal">
        <x-ui.dialog-content class="max-w-lg">

            <x-ui.tabs value="gasto">
                <x-ui.tabs-list variant="underline" class="w-full">
                    <x-ui.tabs-trigger class="flex-1" value="gasto">Gasto</x-ui.tabs-trigger>
                    <x-ui.tabs-trigger class="flex-1" value="recurrente">Recurrente</x-ui.tabs-trigger>

                </x-ui.tabs-list>

                <x-ui.tabs-content selected value="gasto">

                    <x-ui.dialog-header>
                        <x-ui.dialog-title>{{ $mode === 'create' ? 'Nuevo Gasto' : 'Editar Gasto' }}</x-ui.dialog-title>
                    </x-ui.dialog-header>

                    <div class="grid gap-4 py-4">
                        {{-- Escaneo OCR de ticket --}}
                        <label
                            class="relative flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-lg border-2 border-dashed border-input bg-muted/30 px-4 py-5 text-center transition-colors hover:border-primary/50 hover:bg-muted/50"
                            x-data="{ dragging: false }"
                            x-on:dragover.prevent="dragging = true"
                            x-on:dragleave.prevent="dragging = false"
                            x-on:drop="dragging = false"
                            :class="dragging ? 'border-primary bg-primary/5' : ''"
                        >
                            <input type="file" accept="image/*" capture="environment" class="absolute inset-0 size-full cursor-pointer opacity-0"
                                   wire:model="receiptImage" />

                            <div wire:loading.remove wire:target="receiptImage">
                                <x-lucide-scan-line class="size-6 text-muted-foreground mx-auto mb-1" />
                                <p class="text-sm font-medium">Escanear ticket o factura</p>
                                <p class="text-xs text-muted-foreground">Haz una foto o arrastra una imagen y rellenamos el gasto automáticamente</p>
                                @if($receiptImage)
                                    <p class="text-xs text-emerald-600 mt-1 font-medium">✓ {{ $ocrEmpresa ?? 'Imagen procesada' }}</p>
                                @endif
                            </div>

                            <div wire:loading.flex wire:target="receiptImage" class="flex-col items-center gap-1.5">
                                <x-lucide-loader-2 class="size-6 animate-spin text-primary" />
                                <p class="text-sm font-medium text-primary">Leyendo ticket...</p>
                            </div>

                            <x-ui.field-error :messages="$errors->get('receiptImage')" />
                        </label>

                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.field>
                                <x-ui.label>Código</x-ui.label>
                                <x-ui.input wire:model="codigo" value="" placeholder="código" tabindex="3" />
                                <x-ui.field-error :messages="$errors->get('codigo')" />
                            </x-ui.field>
                            <x-ui.field>
                                <x-ui.label>Categoría</x-ui.label>
                                <x-ui.select native wire:model="category_id" class="w-full"
                                             tabindex="1"
                                             x-on:change="$wire.selectCategoria($event.target.value)"
                                >
                                    <option value="">Sin categoría</option>
                                    @foreach($this->categories as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </x-ui.select>
                                <x-ui.field-error :messages="$errors->get('category_id')" />
                            </x-ui.field>
                            <x-ui.field>
                                <x-ui.label>Proveedor</x-ui.label>
                                <x-ui.select native wire:model="proveedor_id" class="w-full"
                                >
                                    <option value="">Sin proveedor</option>
                                    @foreach($this->clientes as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field>
                                <x-ui.label>Fecha</x-ui.label>
                                <x-ui.input type="date" wire:model="date" tabindex="2" />
                                <x-ui.field-error :messages="$errors->get('date')" />
                            </x-ui.field>
                            <x-ui.field>
                                <x-ui.label>Descripción</x-ui.label>
                                <x-ui.input wire:model="description" value="{{ $this->category_id }} - {{ $this->date }}" placeholder="Ej. Alquiler oficina" tabindex="3" />
                                <x-ui.field-error :messages="$errors->get('description')" />
                            </x-ui.field>

                            <div class="grid grid-cols-2 gap-4">
                                <x-ui.field>
                                    <x-ui.label>Importe</x-ui.label>
                                    <x-ui.input type="number" step="0.01" wire:model="amount" placeholder="0,00" tabindex="4" />
                                    <x-ui.field-error :messages="$errors->get('amount')" />
                                </x-ui.field>


                            </div>

                        </div>

                    </div>

                    <x-ui.dialog-footer>
                        <x-ui.button variant="ghost" @click="$wire.showModal = false">Cancelar</x-ui.button>
                        @if($mode === 'create')
                            <x-ui.button tabindex="5"  wire:click="save">Guardar</x-ui.button>
                        @else
                            <x-ui.button wire:click="update">Actualizar</x-ui.button>
                        @endif
                        <button wire:click="delete({{ $expense->id }})" variant="ghost" type="button">
                            Eliminar
                        </button>
                    </x-ui.dialog-footer>
                </x-ui.tabs-content>

                <x-ui.tabs-content value="recurrente">

                    <x-ui.dialog-header>
                        <x-ui.dialog-title>Nuevo Gasto Recurrente</x-ui.dialog-title>
                    </x-ui.dialog-header>

                    <div class="grid gap-4 py-4">
                        <x-ui.field>
                            <x-ui.label>Categoría</x-ui.label>
                            <x-ui.select native wire:model="category_id" class="w-full">
                                <option value="">Sin categoría</option>
                                @foreach($this->categories as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.field-error :messages="$errors->get('category_id')" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Descripción</x-ui.label>
                            <x-ui.input wire:model="description" placeholder="Ej. Alquiler oficina" />
                            <x-ui.field-error :messages="$errors->get('description')" />
                        </x-ui.field>

                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.field>
                                <x-ui.label>Importe</x-ui.label>
                                <x-ui.input type="number" step="0.01" wire:model="amount" placeholder="0,00" />
                                <x-ui.field-error :messages="$errors->get('amount')" />
                            </x-ui.field>

                            <x-ui.field>
                                <x-ui.label>Frecuencia</x-ui.label>
                                <x-ui.select native wire:model="frequency" class="w-full">
                                    <option value="daily">Diaria</option>
                                    <option value="weekly">Semanal</option>
                                    <option value="monthly">Mensual</option>
                                    <option value="yearly">Anual</option>
                                </x-ui.select>
                                <x-ui.field-error :messages="$errors->get('frequency')" />
                            </x-ui.field>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.field>
                                <x-ui.label>Fecha inicio</x-ui.label>
                                <x-ui.input type="date" wire:model="date" />
                                <x-ui.field-error :messages="$errors->get('date')" />
                            </x-ui.field>

                            <x-ui.field>
                                <x-ui.label>Fecha fin (opcional)</x-ui.label>
                                <x-ui.input type="date" wire:model="recurringEndDate" />
                                <x-ui.field-error :messages="$errors->get('recurringEndDate')" />
                            </x-ui.field>
                        </div>
                    </div>

                    <x-ui.dialog-footer>
                        <x-ui.button variant="ghost" @click="$wire.showModal = false">Cancelar</x-ui.button>
                        <x-ui.button wire:click="saveRecurring">Guardar Recurrente</x-ui.button>
                    </x-ui.dialog-footer>

                </x-ui.tabs-content>
            </x-ui.tabs>
        </x-ui.dialog-content>
    </x-ui.dialog>

    {{-- Modal --}}
    <x-ui.dialog wire:model="showModalCategory">
        <x-ui.dialog-content class="max-w-lg">
            <x-ui.dialog-header>
                <x-ui.dialog-title>{{ $mode === 'create' ? 'Nueva Categoría' : 'Editar Categoría' }}</x-ui.dialog-title>
            </x-ui.dialog-header>

            <div class="grid gap-4 py-4">
                <x-ui.field>
                    <x-ui.label>Nombre</x-ui.label>
                    <x-ui.input wire:model="name" placeholder="Ej. Alquiler oficina" />
                    <x-ui.field-error :messages="$errors->get('name')" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Tipo</x-ui.label>
                    <x-ui.select native wire:model="type" class="w-full">
                        <option value="">Sin categoría</option>
                        <option selected value="expense">Gasto</option>
                        <option value="income">Entrada</option>
                    </x-ui.select>
                    <x-ui.field-error :messages="$errors->get('type')" />
                </x-ui.field>
            </div>

            <x-ui.dialog-footer>
                <x-ui.button variant="ghost" @click="$wire.showModalCategory = false">Cancelar</x-ui.button>
                @if($mode === 'create')
                    <x-ui.button wire:click="saveCategory">Guardar</x-ui.button>
                @else
                    <x-ui.button wire:click="update">Actualizar</x-ui.button>
                @endif
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
<x-ui.sonner position="bottom-right" />

</div>
