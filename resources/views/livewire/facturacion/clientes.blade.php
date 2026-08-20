@php
    $clientes     = $this->clientes;
    $empresas     = $this->empresas;
    $empresaFilter = $this->empresaFilter;
    $tipoFilter = $this->tipoFilter;
@endphp
<div x-data="{
        showStats: true,
        showFilters: false,
        selectPrimary('clientes')
    }">

    <header class="bg-background sticky top-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b px-4 lg:px-6">
        <x-ui.sidebar-trigger class="-ml-1" />
        <x-ui.separator orientation="vertical" class="mr-2 data-[orientation=vertical]:h-4" />
        <h1 class="text-base font-medium">Clientes</h1>
        <div class="ml-auto flex items-center gap-2">
            <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Buscar cliente, DNI o email…" class="w-64">
                <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
            </x-ui.input>
                <x-ui.button variant="outline" size="sm" @click="showFilters = !showFilters"
                    ::class="showFilters ? 'bg-accent' : ''">
                    <x-lucide-filter class="size-3.5" />
                    Filtrar
                @if($search || $empresaFilter || $tipoFilter )
                        <x-ui.badge class="ml-1 size-4 p-0 flex items-center justify-center text-[9px]">!</x-ui.badge>
                    @endif
                </x-ui.button>
            <x-ui.button type="button" size="sm" variant="{{ $viewMode === 'cards' ? 'default' : 'ghost' }}" wire:click="$set('viewMode', 'cards')">
                <x-lucide-layout-grid class="size-4" />
            </x-ui.button>
            <x-ui.button type="button" size="sm" variant="{{ $viewMode === 'table' ? 'default' : 'ghost' }}" wire:click="$set('viewMode', 'table')">
                <x-lucide-table class="size-4" />
            </x-ui.button>
        </div>
    </header>


      {{-- Row 3: Filters panel --}}
        <div
            x-show="showFilters"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            x-cloak
            class="px-4 py-3 border-t border-neutral-100 bg-neutral-50/50"
        >
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Buscar</label>
                    <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Nº factura, cliente, NIF…">
                        <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
                    </x-ui.input>
                </div>
               
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Cliente</label>
                    <x-ui.select native size="sm" wire:model.live="empresaFilter" class="w-full">
                        <option value="">Todas</option>
                        @foreach($empresas as $r)
                            <option value="{{ $r->id }}">{{ $r->nombre }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Remesa</label>
                    <x-ui.select native size="sm" wire:model.live="tipoFilter" class="w-full">
                        <option value="">Tipo de cliente</option>
                            <option value="1">Cliente</option>
                            <option value="2">Proveedor</option>
                            <option value="3">Empresa</option>
                    </x-ui.select>
                </div>
                @if($search || $empresaFilter || $tipoFilter )
                    <x-ui.button variant="ghost" size="sm" wire:click="clearFilters" class="gap-1 text-xs text-muted-foreground">
                        <x-lucide-x class="size-3" />
                        Limpiar
                    </x-ui.button>
                @endif
            </div>
        </div>
    
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl w-full">

                @if($viewMode === 'cards')
                {{-- GRID FLIP-CARDS --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse($this->clientes as $cliente)

                        <x-ui.flip-card trigger="hover" height="13rem">

                            {{-- FRENTE --}}
                            <x-slot:front class="flex flex-col justify-center h-full">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-primary/10 p-2.5 text-primary shrink-0">
                                        <x-lucide-user class="size-6" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-base font-bold truncate text-foreground">{{ $cliente->nombrecorto }}</h3>
                                        @if($cliente->empresa)
                                            <p class="text-xs text-muted-foreground mt-0.5 truncate">{{ $cliente->empresa->empresa }}</p>
                                        @endif
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                                <x-lucide-file-text class="size-3.5 text-primary" />
                                                {{ $cliente->facturas_count }} factura(s)
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                                <x-lucide-tag class="size-3.5 text-primary" />
                                                {{ $cliente->conceptos_count }} concepto(s)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </x-slot:front>

                            {{-- REVERSO --}}
                            <x-slot:back class="flex flex-col justify-between h-full">
                                <div class="flex-1 overflow-y-auto pr-1 space-y-1">
                                        <h3 class="text-base font-bold truncate text-foreground">{{ $cliente->nombrecorto }}</h3>
                                    @if($cliente->dni)
                                        <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <x-lucide-id-card class="size-3.5 shrink-0" /> {{ $cliente->dni }}
                                        </p>
                                    @endif
                                    @if($cliente->email)
                                        <p class="flex items-center gap-1.5 text-xs text-muted-foreground truncate">
                                            <x-lucide-mail class="size-3.5 shrink-0" /> {{ $cliente->email }}
                                        </p>
                                    @endif
                                    @if($cliente->telefono)
                                        <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <x-lucide-phone class="size-3.5 shrink-0" /> {{ $cliente->telefono }}
                                        </p>
                                    @endif
                                    @if($cliente->direccion)
                                        <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <x-lucide-map-pin class="size-3.5 shrink-0" /> {{ $cliente->direccion }}
                                        </p>
                                    @endif
                                    @if($cliente->poblacion)
                                        <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <x-lucide-map-pin class="size-3.5 shrink-0" /> {{ $cliente->poblacion }}
                                        </p>
                                    @endif
                                    @if(! $cliente->dni && ! $cliente->email && ! $cliente->telefono && ! $cliente->poblacion)
                                        <p class="text-xs italic text-muted-foreground">Sin datos de contacto.</p>
                                    @endif
                                </div>
                                <x-ui.separator class="my-3 shrink-0" />
                                <div class="flex justify-end gap-1.5 shrink-0">
                                    <x-ui.dialog-trigger for="info-cliente-{{ $cliente->id }}">
                                        <x-ui.button size="sm" variant="outline">
                                            <x-lucide-eye class="size-3.5 mr-1" /> Info
                                        </x-ui.button>
                                    </x-ui.dialog-trigger>
                                    <x-ui.dialog-trigger for="conceptos-cliente-{{ $cliente->id }}">
                                        <x-ui.button size="sm" variant="outline">
                                            <x-lucide-tag class="size-3.5 mr-1" /> Conceptos
                                        </x-ui.button>
                                    </x-ui.dialog-trigger>
                                    <x-ui.dialog-trigger for="facturacion-cliente-{{ $cliente->id }}">
                                        <x-ui.button type="button" size="sm" variant="outline"
                                            wire:click="openRecurrencia({{ $cliente->id }})">
                                            <x-lucide-repeat class="size-3.5" />
                                        </x-ui.button>
                                    </x-ui.dialog-trigger>
                                </div>
                            </x-slot:back>

                        </x-ui.flip-card>

                    @empty
                        <div class="col-span-full">
                            <x-ui.card>
                                <x-ui.card-content class="text-center py-12">
                                    <x-lucide-users class="size-12 mx-auto text-muted-foreground mb-4" />
                                    <p class="text-muted-foreground">No hay clientes registrados</p>
                                </x-ui.card-content>
                            </x-ui.card>
                        </div>
                    @endforelse
                </div>
                @else
                {{-- TABLE VIEW --}}
                <div class="border rounded-lg overflow-hidden">
                    <x-ui.table>
                        <x-ui.table-header>
                            <x-ui.table-row>
                                <x-ui.table-head>Cliente</x-ui.table-head>
                                <x-ui.table-head>Empresa</x-ui.table-head>
                                <x-ui.table-head>Contacto</x-ui.table-head>
                                <x-ui.table-head class="text-right">Facturas</x-ui.table-head>
                                <x-ui.table-head class="text-right">Conceptos</x-ui.table-head>
                                <x-ui.table-head class="w-24"></x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @forelse($this->clientes as $cliente)
                                <x-ui.table-row wire:key="cliente-{{ $cliente->id }}">
                                    <x-ui.table-cell>
                                        <div class="font-medium text-sm">{{ $cliente->nombrecorto }}</div>
                                        @if($cliente->dni)
                                            <div class="text-[10px] text-muted-foreground">{{ $cliente->dni }}</div>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        {{ optional($cliente->empresa_nombre)->empresa ?? '—' }}
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        @if($cliente->email)
                                            <div class="text-xs">{{ $cliente->email }}</div>
                                        @endif
                                        @if($cliente->telefono)
                                            <div class="text-xs text-muted-foreground">{{ $cliente->telefono }}</div>
                                        @endif
                                        @if(! $cliente->email && ! $cliente->telefono)
                                            <span class="text-xs text-muted-foreground">—</span>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-right tabular-nums text-sm">
                                        {{ $cliente->facturas_count }}
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-right tabular-nums text-sm">
                                        {{ $cliente->conceptos_count }}
                                    </x-ui.table-cell>
                                    <x-ui.table-cell>
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.dialog-trigger for="info-cliente-{{ $cliente->id }}">
                                                <x-ui.button type="button" size="sm" variant="ghost" title="Ver info">
                                                    <x-lucide-eye class="size-3.5" />
                                                </x-ui.button>
                                            </x-ui.dialog-trigger>
                                            <x-ui.dialog-trigger for="conceptos-cliente-{{ $cliente->id }}">
                                                <x-ui.button type="button" size="sm" variant="ghost" title="Conceptos">
                                                    <x-lucide-tag class="size-3.5" />
                                                </x-ui.button>
                                            </x-ui.dialog-trigger>
                                            <x-ui.dialog-trigger for="facturacion-cliente-{{ $cliente->id }}">
                                                <x-ui.button type="button" size="sm" variant="ghost"
                                                    wire:click="openRecurrencia({{ $cliente->id }})" title="Recurrencia">
                                                    <x-lucide-repeat class="size-3.5" />
                                                </x-ui.button>
                                            </x-ui.dialog-trigger>
                                        </div>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @empty
                                <x-ui.table-row>
                                    <x-ui.table-cell colspan="6" class="text-center text-muted-foreground py-8 text-sm">
                                        No hay clientes registrados
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @endforelse
                        </x-ui.table-body>
                    </x-ui.table>
                </div>
                @endif

            </div>


    {{-- ══════════════════════════════════════════════
         MODALES — patrón UKM, fuera del grid
    ══════════════════════════════════════════════ --}}
    @foreach($this->clientes as $cliente)

        {{-- MODAL INFO / EDITAR --}}
        <x-ui.dialog id="info-cliente-{{ $cliente->id }}">
            <x-ui.dialog-content class="sm:max-w-lg">
                <x-ui.dialog-header>
                    <x-ui.dialog-title>{{ $cliente->nombrecorto }}</x-ui.dialog-title>
                    <x-ui.dialog-description>Datos del cliente y edición</x-ui.dialog-description>
                </x-ui.dialog-header>

                <div x-data="{ editing: false }">
                    {{-- Vista info --}}
                    <div x-show="!editing">
                        <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">DNI / NIF</dt>
                                <dd class="mt-0.5 font-medium">{{ $cliente->dni ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Teléfono</dt>
                                <dd class="mt-0.5">{{ $cliente->telefono ?: '—' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Email</dt>
                                <dd class="mt-0.5">{{ $cliente->email ?: '—' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Dirección</dt>
                                <dd class="mt-0.5">{{ $cliente->domicilio ?: '—' }}@if($cliente->poblacion), {{ $cliente->poblacion }}@endif</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Empresa</dt>
                                <dd class="mt-0.5">{{ $cliente->empresa?->empresa ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Domiciliado</dt>
                                <dd class="mt-0.5">{{ $cliente->domiciliado ? 'Sí' : 'No' }}</dd>
                            </div>
                            @if($cliente->observaciones)
                            <div class="col-span-2">
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Observaciones</dt>
                                <dd class="mt-0.5 text-muted-foreground">{{ $cliente->observaciones }}</dd>
                            </div>
                            @endif
                        </dl>

                        <x-ui.dialog-footer class="mt-6 flex justify-between items-center">
                            <x-ui.button type="button" size="sm" wire:click="openEdit({{ $cliente->id }})" @click="editing = true">
                                <x-lucide-pencil class="size-3.5 mr-1" /> Editar
                            </x-ui.button>
                                                        <x-ui.button type="button" size="sm" wire:click="deleteCliente({{ $cliente->id }})"  @click="editing = false">
                                <x-lucide-save class="size-3.5 mr-1" /> Eliminar
                            </x-ui.button>
                            <x-ui.dialog-close>
                                <x-ui.button type="button" variant="outline" size="sm">Cerrar</x-ui.button>
                            </x-ui.dialog-close>
                        </x-ui.dialog-footer>
                    </div>

                    {{-- Vista editar --}}
                    <div x-show="editing" x-cloak>
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <x-ui.field label="Nombre / Razón social">
                                    <x-ui.input wire:model="form.nombretotal" placeholder="Nombre completo" />
                                    @error('form.nombretotal') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                                </x-ui.field>
                            </div>
                            <x-ui.field label="DNI / NIF / CIF">
                                <x-ui.input wire:model="form.dni" placeholder="B12345678" />
                            </x-ui.field>
                            <x-ui.field label="Teléfono">
                                <x-ui.input wire:model="form.telefono" placeholder="600 000 000" />
                            </x-ui.field>
                            <div class="col-span-2">
                                <x-ui.field label="Email">
                                    <x-ui.input type="email" wire:model="form.email" placeholder="cliente@ejemplo.com" />
                                </x-ui.field>
                            </div>
                            <div class="col-span-2">
                                <x-ui.field label="Domicilio">
                                    <x-ui.input wire:model="form.domicilio" placeholder="Calle, número..." />
                                </x-ui.field>
                            </div>
                            <x-ui.field label="Población">
                                <x-ui.input wire:model="form.poblacion" />
                            </x-ui.field>
                            <x-ui.field label="Código postal">
                                <x-ui.input wire:model="form.codigopostal" />
                            </x-ui.field>
                            <div class="col-span-2">
                                <x-ui.field label="Empresa">
                                    <x-ui.select native wire:model="form.empresa_id" class="w-full">
                                        <option value="">Sin empresa</option>
                                        @foreach($this->empresas as $empresa)
                                            <option value="{{ $empresa->id }}">{{ $empresa->empresa }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </x-ui.field>
                            </div>
                            <div class="col-span-2 flex items-center gap-2 h-9">
                                <input type="checkbox" wire:model="form.domiciliado"
                                    id="domiciliado-{{ $cliente->id }}"
                                    class="rounded border-input size-4 cursor-pointer" />
                                <label for="domiciliado-{{ $cliente->id }}" class="text-sm cursor-pointer">Domiciliado bancario</label>
                            </div>
                            <div class="col-span-2">
                                <x-ui.field label="Observaciones">
                                    <textarea wire:model="form.observaciones" rows="2"
                                        class="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] outline-none resize-none"></textarea>
                                </x-ui.field>
                            </div>
                        </div>

                        <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                            <x-ui.button type="button" variant="outline" size="sm" @click="editing = false">Cancelar</x-ui.button>
                            <x-ui.button type="button" size="sm" wire:click="saveCliente">
                                <x-lucide-save class="size-3.5 mr-1" /> Guardar
                            </x-ui.button>
                            <x-ui.button type="button" size="sm" wire:click="deleteCliente">
                                <x-lucide-save class="size-3.5 mr-1" /> Eliminar
                            </x-ui.button>
                        </x-ui.dialog-footer>


                    </div>
                </div>
            </x-ui.dialog-content>
        </x-ui.dialog>


        {{-- MODAL CONCEPTOS --}}
        <x-ui.dialog id="conceptos-cliente-{{ $cliente->id }}">
            <x-ui.dialog-content class="sm:max-w-3xl">
                <x-ui.dialog-header>
                    <x-ui.dialog-title>Conceptos: {{ $cliente->nombrecorto }}</x-ui.dialog-title>
                    <x-ui.dialog-description>Conceptos de facturación asociados a este cliente</x-ui.dialog-description>
                </x-ui.dialog-header>

                @php
                    $conceptosCliente = \App\Models\Concepto::where('cliente_id', $cliente->id)
                        ->orderBy('concepto')
                        ->get();
                @endphp

                <div class="mt-4 border rounded-lg overflow-hidden">
                    <x-ui.table>
                        <x-ui.table-header>
                            <x-ui.table-row>
                                <x-ui.table-head class="py-2">Concepto</x-ui.table-head>
                                <x-ui.table-head class="py-2">Unidad</x-ui.table-head>
                                <x-ui.table-head class="py-2 text-right">Precio</x-ui.table-head>
                                <x-ui.table-head class="py-2 text-right">Dto.%</x-ui.table-head>
                                <x-ui.table-head class="py-2 text-right">IGIC%</x-ui.table-head>
                                <x-ui.table-head class="py-2 text-right">Ret.%</x-ui.table-head>
                                <x-ui.table-head class="py-2 text-center">Recurrente</x-ui.table-head>
                                <x-ui.table-head class="py-2 w-16"></x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @forelse($conceptosCliente as $concepto)
                                <x-ui.table-row>
                                    <x-ui.table-cell class="py-2 font-medium">
                                        <div>{{ $concepto->concepto }}</div>
                                        @if($concepto->categoria)
                                            <div class="text-[10px] text-muted-foreground">{{ $concepto->categoria }}</div>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-xs">{{ $concepto->unidad }}</x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-right tabular-nums text-sm font-medium">{{ number_format($concepto->precio, 2, ',', '.') }} €</x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-right text-xs text-muted-foreground">{{ $concepto->descuento }}%</x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-right text-xs text-muted-foreground">{{ $concepto->impuesto }}%</x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-right text-xs text-muted-foreground">{{ $concepto->retenciones }}%</x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-center">
                                        @if($concepto->recurrente)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sí</span>
                                        @else
                                            <span class="text-xs text-muted-foreground">—</span>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="py-2">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.dialog-trigger for="edit-concepto-{{ $concepto->id }}">
                                                <x-ui.button type="button" size="sm" variant="ghost"
                                                    wire:click="openEditConcepto({{ $concepto->id }})">
                                                    <x-lucide-pencil class="size-3.5" />
                                                </x-ui.button>
                                            </x-ui.dialog-trigger>
                                            <x-ui.button type="button" size="sm" variant="ghost"
                                                class="text-destructive hover:text-destructive"
                                                wire:click="deleteConcepto({{ $concepto->id }})"
                                                wire:confirm="¿Eliminar este concepto?">
                                                <x-lucide-trash-2 class="size-3.5" />
                                            </x-ui.button>
                                        </div>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @empty
                                <x-ui.table-row>
                                    <x-ui.table-cell colspan="8" class="text-center text-muted-foreground py-8 text-sm">
                                        Sin conceptos para este cliente.
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @endforelse
                        </x-ui.table-body>
                    </x-ui.table>
                </div>

                <x-ui.dialog-footer class="mt-4">
                    <x-ui.dialog-close>
                        <x-ui.button type="button" variant="outline" size="sm">Cerrar</x-ui.button>
                    </x-ui.dialog-close>
                </x-ui.dialog-footer>
            </x-ui.dialog-content>
        </x-ui.dialog>


        {{-- MODAL FACTURACIÓN RECURRENTE --}}
        <x-ui.dialog id="facturacion-cliente-{{ $cliente->id }}">
            <x-ui.dialog-content class="sm:max-w-md">
                <x-ui.dialog-header>
                    <x-ui.dialog-title>Facturación recurrente</x-ui.dialog-title>
                    <x-ui.dialog-description>{{ $cliente->nombrecorto }}</x-ui.dialog-description>
                </x-ui.dialog-header>

                <div class="mt-4 space-y-4">
                    <div class="rounded-lg border bg-muted/30 p-4 flex items-start gap-3">
                        <x-lucide-info class="size-4 text-primary mt-0.5 shrink-0" />
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            Configura el día del mes en que se generarán automáticamente las facturas recurrentes para los conceptos activos de este cliente.
                        </p>
                    </div>

                    <x-ui.field label="Día de emisión mensual">
                        <div class="flex items-center gap-2">
                            <x-ui.input type="number" wire:model="recurrencia.dia" min="1" max="28" class="w-24" />
                            <span class="text-sm text-muted-foreground">de cada mes</span>
                        </div>
                    </x-ui.field>

                    <x-ui.field label="Estado">
                        <div class="flex items-center gap-2 h-9">
                            <input type="checkbox" wire:model="recurrencia.activa"
                                id="recurrencia-activa-{{ $cliente->id }}"
                                class="rounded border-input size-4 cursor-pointer" />
                            <label for="recurrencia-activa-{{ $cliente->id }}" class="text-sm cursor-pointer">
                                Facturación automática activada
                            </label>
                        </div>
                    </x-ui.field>

                    <x-ui.field label="Notas">
                        <textarea wire:model="recurrencia.notas" rows="2" placeholder="Notas sobre la facturación..."
                            class="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] outline-none resize-none"></textarea>
                    </x-ui.field>
                </div>

                <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                    <x-ui.dialog-close>
                        <x-ui.button type="button" variant="outline" size="sm">Cancelar</x-ui.button>
                    </x-ui.dialog-close>
                    <x-ui.dialog-close>
                        <x-ui.button type="button" size="sm" wire:click="saveRecurrencia">
                            <x-lucide-save class="size-3.5 mr-1" /> Guardar
                        </x-ui.button>
                    </x-ui.dialog-close>
                </x-ui.dialog-footer>
            </x-ui.dialog-content>
        </x-ui.dialog>

        {{-- MODALES EDITAR CONCEPTO (uno por concepto) --}}
        @php
            $conceptosParaModales = \App\Models\Concepto::where('cliente_id', $cliente->id)->orderBy('concepto')->get();
        @endphp
        @foreach($conceptosParaModales as $concepto)
            <x-ui.dialog id="edit-concepto-{{ $concepto->id }}">
                <x-ui.dialog-content class="sm:max-w-md">
                    <x-ui.dialog-header>
                        <x-ui.dialog-title>Editar concepto</x-ui.dialog-title>
                        <x-ui.dialog-description>{{ $cliente->nombrecorto }}</x-ui.dialog-description>
                    </x-ui.dialog-header>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <x-ui.field label="Concepto">
                                <x-ui.input wire:model="conceptoForm.concepto" placeholder="Nombre del concepto" />
                                @error('conceptoForm.concepto') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                            </x-ui.field>
                        </div>
                        <x-ui.field label="Unidad">
                            <x-ui.input wire:model="conceptoForm.unidad" placeholder="UNID, MES, HORA…" />
                        </x-ui.field>
                        <x-ui.field label="Precio (€)">
                            <x-ui.input type="number" step="0.01" wire:model="conceptoForm.precio" />
                        </x-ui.field>
                        <x-ui.field label="Descuento %">
                            <x-ui.input type="number" step="0.01" wire:model="conceptoForm.descuento" />
                        </x-ui.field>
                        <x-ui.field label="IGIC %">
                            <x-ui.input type="number" step="0.01" wire:model="conceptoForm.impuesto" />
                        </x-ui.field>
                        <x-ui.field label="Retención %">
                            <x-ui.input type="number" step="0.01" wire:model="conceptoForm.retenciones" />
                        </x-ui.field>
                        <x-ui.field label="Categoría">
                            <x-ui.select native wire:model="conceptoForm.categoria" class="w-full">
                                <option value="">Sin categoría</option>
                                @foreach(\App\Models\Concepto::categorias() as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Recurrente">
                            <label class="flex items-center gap-2 rounded-md border px-3 py-2 cursor-pointer hover:bg-accent">
                                <input type="checkbox" wire:model="conceptoForm.recurrente" class="size-4 rounded border-input" />
                                <span class="text-sm">Incluir en remesas de facturación recurrente</span>
                            </label>
                        </x-ui.field>
                        <div class="col-span-2">
                            <x-ui.field label="Observaciones">
                                <textarea wire:model="conceptoForm.observaciones" rows="2"
                                    class="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] outline-none resize-none"></textarea>
                            </x-ui.field>
                        </div>
                    </div>

                    <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                        <x-ui.dialog-close>
                            <x-ui.button type="button" variant="outline" size="sm">Cancelar</x-ui.button>
                        </x-ui.dialog-close>
                        <x-ui.dialog-close>
                            <x-ui.button type="button" size="sm" wire:click="saveConcepto">
                                <x-lucide-save class="size-3.5 mr-1" /> Guardar
                            </x-ui.button>
                        </x-ui.dialog-close>
                    </x-ui.dialog-footer>
                </x-ui.dialog-content>
            </x-ui.dialog>
        @endforeach

    @endforeach

</div>
