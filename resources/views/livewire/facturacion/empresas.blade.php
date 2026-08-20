<div x-data="selectPrimary('empresas')">

    <header class="bg-background sticky top-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b px-4 lg:px-6">
        <x-ui.sidebar-trigger class="-ml-1" />
        <x-ui.separator orientation="vertical" class="mr-2 data-[orientation=vertical]:h-4" />
        <h1 class="text-base font-medium">Empresas</h1>
        <div class="ml-auto flex items-center gap-2">
            <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Buscar empresa o NIF…" class="w-56">
                <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
            </x-ui.input>
        </div>
    </header>
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl w-full">

                    {{-- GRID FLIP-CARDS --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @forelse($this->empresas as $empresa)

                            <x-ui.flip-card trigger="hover" height="13rem">

                                {{-- FRENTE --}}
                                <x-slot:front class="flex flex-col justify-center h-full">
                                    <div class="flex items-center gap-3">
                                        <div class="rounded-lg bg-primary/10 p-2.5 text-primary shrink-0">
                                            <x-lucide-building-2 class="size-6" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-base font-bold truncate text-foreground">{{ $empresa->nombre }}</h4>
                                            <h4 class="text-base font-bold truncate text-foreground">{{ $empresa->empresa }}</h4>
                                            <span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground mt-1 font-medium">
                                                <x-lucide-file-text class="size-3.5 text-primary" />
                                                {{ $empresa->facturas_count }} factura(s)
                                            </span>
                                            <span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground mt-1 font-medium">
                                                <x-lucide-file-text class="size-3.5 text-primary" />
                                                {{ $empresa->clientes_count }} cliente(s)
                                            </span>
                                            @if($empresa->nif)
                                                <p class="text-xs text-muted-foreground mt-0.5">{{ $empresa->nif }}</p>
                                            @endif
                                            @if($empresa->logo_empresa)
                                                <img src="/{{ $empresa->logo_empresa }}" style=" display: block;width: 165px;" alt="Logo">
                                            @endif
                                        </div>
                                    </div>
                                </x-slot:front>

                                {{-- REVERSO --}}
                                <x-slot:back class="flex flex-col justify-between h-full">
                                    <div class="flex-1 overflow-y-auto pr-1 text-sm text-muted-foreground leading-relaxed space-y-1">
                                        @if($empresa->email)
                                            <p class="flex items-center gap-1.5 text-xs">
                                                <x-lucide-mail class="size-3.5 shrink-0" /> {{ $empresa->email }}
                                            </p>
                                        @endif
                                        @if($empresa->telefono)
                                            <p class="flex items-center gap-1.5 text-xs">
                                                <x-lucide-phone class="size-3.5 shrink-0" /> {{ $empresa->telefono }}
                                            </p>
                                        @endif
                                        @if($empresa->poblacion)
                                            <p class="flex items-center gap-1.5 text-xs">
                                                <x-lucide-map-pin class="size-3.5 shrink-0" /> {{ $empresa->poblacion }}
                                            </p>
                                        @endif
                                        @if(! $empresa->email && ! $empresa->telefono && ! $empresa->poblacion)
                                            <p class="text-xs italic">Sin datos de contacto.</p>
                                        @endif
                                    </div>
                                    <x-ui.separator class="my-3 shrink-0" />
                                    <div class="flex justify-end gap-1.5 shrink-0">
                                        <x-ui.dialog-trigger for="info-empresa-{{ $empresa->id }}">
                                            <x-ui.button size="sm" variant="outline">
                                                <x-lucide-eye class="size-3.5 mr-1" />
                                                Info
                                            </x-ui.button>
                                        </x-ui.dialog-trigger>
                                        <x-ui.dialog-trigger for="conceptos-empresa-{{ $empresa->id }}">
                                            <x-ui.button size="sm" variant="outline">
                                                <x-lucide-tag class="size-3.5 mr-1" />
                                                Conceptos
                                            </x-ui.button>
                                        </x-ui.dialog-trigger>
                                        <x-ui.dialog-trigger for="facturacion-empresa-{{ $empresa->id }}">
                                            <x-ui.button size="sm" variant="outline">
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
                                        <x-lucide-building-2 class="size-12 mx-auto text-muted-foreground mb-4" />
                                        <p class="text-muted-foreground">No hay empresas registradas</p>
                                    </x-ui.card-content>
                                </x-ui.card>
                            </div>
                        @endforelse
                    </div>
                </div>


    {{-- ══════════════════════════════════════════════
         MODALES — fuera del grid (patrón UKM)
    ══════════════════════════════════════════════ --}}
    @foreach($this->empresas as $empresa)

        {{-- MODAL INFO / EDITAR --}}
        <x-ui.dialog id="info-empresa-{{ $empresa->id }}">
            <x-ui.dialog-content class="sm:max-w-lg">
                <x-ui.dialog-header>
                    <x-ui.dialog-title>{{ $empresa->empresa }}</x-ui.dialog-title>
                    <x-ui.dialog-description>Datos de la empresa y edición</x-ui.dialog-description>
                </x-ui.dialog-header>

                <div x-data="{ editing: false }">
                    {{-- Vista info --}}
                    <div x-show="!editing">
                        <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Logo</dt>
                                <dd class="mt-0.5 font-medium">{{ $empresa->logo ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">NIF / CIF</dt>
                                <dd class="mt-0.5 font-medium">{{ $empresa->nif ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Teléfono</dt>
                                <dd class="mt-0.5">{{ $empresa->telefono ?: '—' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Email</dt>
                                <dd class="mt-0.5">{{ $empresa->email ?: '—' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Dirección</dt>
                                <dd class="mt-0.5">{{ $empresa->direccion ?: '—' }}@if($empresa->poblacion), {{ $empresa->poblacion }}@endif</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Administrador</dt>
                                <dd class="mt-0.5">{{ $empresa->administrador ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Facturas</dt>
                                <dd class="mt-0.5">{{ $empresa->facturas_count }}</dd>
                            </div>
                            @if($empresa->observaciones)
                            <div class="col-span-2">
                                <dt class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Observaciones</dt>
                                <dd class="mt-0.5 text-muted-foreground">{{ $empresa->observaciones }}</dd>
                            </div>
                            @endif
                        </dl>

                        <x-ui.dialog-footer class="mt-6 flex justify-between items-center">
                            <x-ui.button type="button" size="sm" @click="editing = true">
                                <x-lucide-pencil class="size-3.5 mr-1" /> Editar
                            </x-ui.button>
                            <x-ui.dialog-close>
                                <x-ui.button type="button" variant="outline" size="sm">Cerrar</x-ui.button>
                            </x-ui.dialog-close>
                        </x-ui.dialog-footer>
                    </div>

                    {{-- Vista editar (inline) --}}
                    <div x-show="editing" x-cloak>
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <x-ui.field label="Razón social">
                                    <x-ui.input wire:model="form.empresa" placeholder="Nombre de la empresa" />
                                    @error('form.empresa') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                                </x-ui.field>
                                <x-ui.field label="Nombre Comercial">
                                    <x-ui.input wire:model="form.nombre" placeholder="Nombre Comercial" />
                                </x-ui.field>
                            </div>
                            <x-ui.field label="Logo">
                                <x-ui.input wire:model="form.logo_empresa" placeholder="URL del logo" />
                            </x-ui.field>
                            <x-ui.field label="NIF / CIF">
                                <x-ui.input wire:model="form.nif" placeholder="B12345678" />
                            </x-ui.field>
                            <x-ui.field label="Teléfono">
                                <x-ui.input wire:model="form.telefono" placeholder="922 000 000" />
                            </x-ui.field>
                            <div class="col-span-2">
                                <x-ui.field label="Email">
                                    <x-ui.input type="email" wire:model="form.email" placeholder="empresa@ejemplo.com" />
                                </x-ui.field>
                            </div>
                            <div class="col-span-2">
                                <x-ui.field label="Dirección">
                                    <x-ui.input wire:model="form.direccion" placeholder="Calle, número..." />
                                </x-ui.field>
                            </div>
                            <x-ui.field label="Población">
                                <x-ui.input wire:model="form.poblacion" />
                            </x-ui.field>
                            <x-ui.field label="Administrador">
                                <x-ui.input wire:model="form.administrador" />
                            </x-ui.field>
                            <div class="col-span-2">
                                <x-ui.field label="Observaciones">
                                    <textarea wire:model="form.observaciones" rows="2"
                                        class="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] outline-none resize-none"></textarea>
                                </x-ui.field>
                            </div>
                        </div>

                        <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                            <x-ui.button type="button" variant="outline" size="sm" @click="editing = false">Cancelar</x-ui.button>
                            <x-ui.button type="button" size="sm" wire:click="openEdit({{ $empresa->id }})" @click="editing = true">
                                Cargar datos
                            </x-ui.button>
                            <x-ui.button type="button" size="sm" wire:click="saveEmpresa" @click="if (!$wire.editingId) $wire.openEdit({{ $empresa->id }})">
                                <x-lucide-save class="size-3.5 mr-1" /> Guardar
                            </x-ui.button>
                        </x-ui.dialog-footer>
                    </div>
                </div>
            </x-ui.dialog-content>
        </x-ui.dialog>


        {{-- MODAL CONCEPTOS --}}
        <x-ui.dialog id="conceptos-empresa-{{ $empresa->id }}">
            <x-ui.dialog-content class="sm:max-w-2xl">
                <x-ui.dialog-header>
                    <x-ui.dialog-title>Conceptos: {{ $empresa->empresa }}</x-ui.dialog-title>
                    <x-ui.dialog-description>Conceptos asociados a los clientes de esta empresa</x-ui.dialog-description>
                </x-ui.dialog-header>

                @php
                    $conceptosEmpresa = \App\Models\Concepto::query()
                        ->whereHas('cliente', fn ($q) => $q->where('empresa_id', $empresa->id))
                        ->with('cliente')
                        ->orderBy('concepto')
                        ->get();
                @endphp

                <div class="mt-4 border rounded-lg overflow-hidden max-h-72 overflow-y-auto">
                    <x-ui.table>
                        <x-ui.table-header>
                            <x-ui.table-row>
                                <x-ui.table-head class="py-2">Concepto</x-ui.table-head>
                                <x-ui.table-head class="py-2">Cliente</x-ui.table-head>
                                <x-ui.table-head class="py-2">Unidad</x-ui.table-head>
                                <x-ui.table-head class="py-2 text-right">Precio</x-ui.table-head>
                                <x-ui.table-head class="py-2 text-right">IGIC</x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @forelse($conceptosEmpresa as $concepto)
                                <x-ui.table-row>
                                    <x-ui.table-cell class="py-2 font-medium">{{ $concepto->concepto }}</x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-xs text-muted-foreground">{{ $concepto->cliente?->nombretotal ?? '—' }}</x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-xs">{{ $concepto->unidad }}</x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-right tabular-nums text-sm">{{ number_format($concepto->precio, 2, ',', '.') }} €</x-ui.table-cell>
                                    <x-ui.table-cell class="py-2 text-right text-xs text-muted-foreground">{{ $concepto->impuesto }}%</x-ui.table-cell>
                                </x-ui.table-row>
                            @empty
                                <x-ui.table-row>
                                    <x-ui.table-cell colspan="5" class="text-center text-muted-foreground py-8 text-sm">
                                        Sin conceptos para esta empresa.
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
        <x-ui.dialog id="facturacion-empresa-{{ $empresa->id }}">
            <x-ui.dialog-content class="sm:max-w-md">
                <x-ui.dialog-header>
                    <x-ui.dialog-title>Facturación recurrente</x-ui.dialog-title>
                    <x-ui.dialog-description>{{ $empresa->empresa }}</x-ui.dialog-description>
                </x-ui.dialog-header>

                <div class="mt-4 space-y-4">
                    <div class="rounded-lg border bg-muted/30 p-4 flex items-start gap-3">
                        <x-lucide-info class="size-4 text-primary mt-0.5 shrink-0" />
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            Configura el día del mes en que se generarán automáticamente las facturas recurrentes para los conceptos activos de esta empresa.
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
                                id="recurrencia-activa-{{ $empresa->id }}"
                                class="rounded border-input size-4 cursor-pointer" />
                            <label for="recurrencia-activa-{{ $empresa->id }}" class="text-sm cursor-pointer">
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
                        <x-ui.button type="button" size="sm">
                            <x-lucide-save class="size-3.5 mr-1" /> Guardar
                        </x-ui.button>
                    </x-ui.dialog-close>
                </x-ui.dialog-footer>
            </x-ui.dialog-content>
        </x-ui.dialog>

    @endforeach

</div>