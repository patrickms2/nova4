
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
    public ?int $selectedClienteId = null;
    public ?Cliente $selectedCliente = null;
    public bool $showResults = false;

    public int $limit = 10;

    public function mount(?int $clienteId = null): void
    {
        if ($clienteId) {
            $this->setClienteById($clienteId);
        }
    }

    public function updatedSearch(): void
    {
        $this->showResults = strlen($this->search) > 1;
        $this->resetSelectedIfSearchChanged();
    }

    protected function resetSelectedIfSearchChanged(): void
    {
        if ($this->selectedCliente && $this->selectedCliente->nombretotal !== $this->search) {
            $this->selectedClienteId = null;
            $this->selectedCliente   = null;
        }
    }

    public function setClienteById(int $id): void
    {
        $cliente = Cliente::find($id);

        if ($cliente) {
            $this->selectedClienteId = $cliente->codcliente ?? $cliente->id;
            $this->selectedCliente   = $cliente;
            $this->search            = $cliente->nombretotal ?? $cliente->nombre ?? '';
            $this->showResults       = false;

            // Emitimos al padre (form de factura)
            $this->emitUp('clienteSelected', $this->selectedClienteId);
        }
    }

    public function selectCliente(int $id): void
    {
        $this->setClienteById($id);
    }

    public function clearCliente(): void
    {
        $this->selectedClienteId = null;
        $this->selectedCliente   = null;
        $this->search            = '';
        $this->showResults       = false;

        $this->emitUp('clienteSelected', null);
    }

    public function getResultadosProperty()
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
}
?>


<div
    x-data="{
        open: @entangle('showResults').defer,
        focusInput() { $refs.searchInput.focus(); },
    }"
    class="space-y-3"
>
    <div class="flex items-center justify-between">
        <div>
            <label class="block text-sm font-medium text-slate-700">
                Cliente
            </label>
            <p class="text-xs text-slate-400">
                Escribe nombre, CIF/NIF, email o teléfono
            </p>
        </div>

        @if($selectedCliente)
            <button
                type="button"
                wire:click="clearCliente"
                class="text-xs px-2 py-1 rounded-full border border-rose-200 text-rose-600 hover:bg-rose-50 transition"
            >
                Quitar cliente
            </button>
        @endif
    </div>

    <div class="relative">
        <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus-within:ring-2 focus-within:ring-emerald-400 focus-within:border-emerald-300 transition">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M5.121 17.804A9 9 0 1118.364 4.56M15 11a3 3 0 11-6 0 3 3 0 016 0zM6.343 19.071A7 7 0 0112 16a7 7 0 015.657 3.071" />
                </svg>
            </span>

            <input
                x-ref="searchInput"
                type="text"
                wire:model.debounce.300ms="search"
                @focus="open = true"
                placeholder="Buscar cliente..."
                class="flex-1 border-0 bg-transparent text-sm focus:outline-none focus:ring-0 placeholder-slate-400"
            />

            @if($selectedCliente)
                <span class="text-[11px] px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    {{ $selectedCliente->codcliente ?? $selectedCliente->id }}
                </span>
            @endif
        </div>

        <div
            x-show="open"
            x-cloak
            @click.away="open = false"
            class="absolute z-30 mt-2 w-full rounded-2xl border border-slate-200 bg-white shadow-xl shadow-emerald-100/40 overflow-hidden"
        >
            @if(strlen($search) < 2)
                <div class="px-4 py-3 text-xs text-slate-400">
                    Empieza a escribir al menos 2 caracteres…
                </div>
            @else
                @if($this->resultados->isEmpty())
                    <div class="px-4 py-3 text-xs text-slate-400">
                        Sin resultados para <span class="font-semibold">"{{ $search }}"</span>
                    </div>
                @else
                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-50">
                        @foreach($this->resultados as $cliente)
                            <button
                                type="button"
                                wire:click="selectCliente({{ $cliente->codcliente ?? $cliente->id }})"
                                class="w-full text-left px-4 py-3 hover:bg-emerald-50/70 transition flex gap-3"
                            >
                                <div class="mt-1">
                                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-xs font-semibold text-white shadow-sm">
                                        {{ mb_substr($cliente->nombretotal ?? $cliente->nombre ?? 'C', 0, 2) }}
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="truncate">
                                            <p class="text-sm font-semibold text-slate-800 truncate">
                                                {{ $cliente->nombretotal ?? $cliente->nombre }}
                                            </p>
                                            @if($cliente->dni)
                                                <p class="text-[11px] text-slate-500 mt-0.5">
                                                    CIF/NIF: <span class="font-mono">{{ $cliente->dni }}</span>
                                                </p>
                                            @endif
                                        </div>
                                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-50 text-slate-500 border border-slate-100">
                                            #{{ $cliente->codcliente ?? $cliente->id }}
                                        </span>
                                    </div>

                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                                        @if($cliente->domicilio || $cliente->poblacion)
                                            <span class="inline-flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" />
                                                    <circle cx="12" cy="11" r="2" />
                                                </svg>
                                                {{ \Illuminate\Support\Str::limit(trim(($cliente->domicilio ? $cliente->domicilio . ', ' : '') . ($cliente->poblacion ?? '')), 45) }}
                                            </span>
                                        @endif

                                        @if($cliente->telefono)
                                            <span class="inline-flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M3 5a2 2 0 012-2h2l2 5-2 1a11 11 0 005 5l1-2 5 2v2a2 2 0 01-2 2h-1C9.82 18.5 5.5 14.18 5 9V8a2 2 0 00-2-2z" />
                                                </svg>
                                                {{ $cliente->telefono }}
                                            </span>
                                        @endif

                                        @if($cliente->web)
                                            <span class="inline-flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M12 4.5v15m7.5-7.5h-15" />
                                                </svg>
                                                {{ \Illuminate\Support\Str::limit(str_replace(['http://', 'https://'], '', $cliente->web), 28) }}
                                            </span>
                                        @endif

                                        @if($cliente->email)
                                            <span class="inline-flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M4 6h16v12H4z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M4 8l8 5 8-5" />
                                                </svg>
                                                {{ \Illuminate\Support\Str::limit($cliente->email, 28) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if($selectedCliente)
        <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-teal-50 px-4 py-3 flex items-start gap-3 shadow-sm">
            <div class="mt-0.5">
                <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs font-bold shadow">
                    {{ mb_substr($selectedCliente->nombretotal ?? $selectedCliente->nombre ?? 'C', 0, 2) }}
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-emerald-900 truncate">
                        {{ $selectedCliente->nombretotal ?? $selectedCliente->nombre }}
                    </p>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-white/80 text-emerald-700 border border-emerald-200">
                        #{{ $selectedCliente->codcliente ?? $selectedCliente->id }}
                    </span>
                </div>
                <div class="mt-1 space-y-0.5 text-[11px] text-emerald-900/80">
                    @if($selectedCliente->dni)
                        <div>
                            <span class="font-semibold">CIF/NIF:</span>
                            <span class="font-mono">{{ $selectedCliente->dni }}</span>
                        </div>
                    @endif

                    @if($selectedCliente->domicilio || $selectedCliente->poblacion || $selectedCliente->codigopostal)
                        <div>
                            <span class="font-semibold">Dirección:</span>
                            {{ trim(
                                ($selectedCliente->domicilio ? $selectedCliente->domicilio.', ' : '')
                                . ($selectedCliente->poblacion ?? '')
                                . ($selectedCliente->codigopostal ? ' ('.$selectedCliente->codigopostal.')' : '')
                            ) }}
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
                        @if($selectedCliente->telefono)
                            <span>📞 {{ $selectedCliente->telefono }}</span>
                        @endif
                        @if($selectedCliente->email)
                            <span>✉️ {{ $selectedCliente->email }}</span>
                        @endif
                        @if($selectedCliente->web)
                            <span>🌐 {{ str_replace(['http://','https://'],'',$selectedCliente->web) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
