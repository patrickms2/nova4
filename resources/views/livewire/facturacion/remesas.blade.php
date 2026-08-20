<div>
    <header class="bg-background sticky top-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b px-4 lg:px-6">
        <x-ui.sidebar-trigger class="-ml-1" />
        <x-ui.separator orientation="vertical" class="mr-2 data-[orientation=vertical]:h-4" />
        <h1 class="text-base font-medium">Remesas</h1>
        <div class="ml-auto flex items-center gap-2">
            <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Buscar remesa…" class="w-64">
                <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
            </x-ui.input>
            <x-ui.button size="sm" @click="$wire.openCreate(); $dispatch('open-dialog-remesa-form')">
                <x-lucide-plus class="size-4" />
                Nueva Remesa
            </x-ui.button>
        </div>
    </header>

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl w-full">
        @php
            $remesas = $this->remesas;
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-4 p-5">
                    <div class="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-xl">
                        <x-lucide-calendar class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ $remesas->count() }}</div>
                        <div class="text-muted-foreground text-sm">Remesas</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-4 p-5">
                    <div class="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-xl">
                        <x-lucide-file-text class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ $remesas->sum('facturas_count') }}</div>
                        <div class="text-muted-foreground text-sm">Facturas generadas</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <x-ui.card>
            <x-ui.card-content class="p-0">
                <x-ui.table>
                    <x-ui.table-header>
                        <x-ui.table-row>
                            <x-ui.table-head>Nombre</x-ui.table-head>
                            <x-ui.table-head>Fecha</x-ui.table-head>
                            <x-ui.table-head>Estado</x-ui.table-head>
                            <x-ui.table-head class="text-right">Clientes</x-ui.table-head>
                            <x-ui.table-head class="text-right">Facturas</x-ui.table-head>
                            <x-ui.table-head class="w-40"></x-ui.table-head>
                        </x-ui.table-row>
                    </x-ui.table-header>
                    <x-ui.table-body>
                        @forelse($remesas as $remesa)
                            <x-ui.table-row>
                                <x-ui.table-cell class="font-medium">{{ $remesa->nombre }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ optional($remesa->fecha)->format('d/m/Y') }}</x-ui.table-cell>
                                <x-ui.table-cell>
                                    @if($remesa->estado === 'generated')
                                        <x-ui.badge variant="outline" class="text-green-600 border-green-600">Generada</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="outline">Borrador</x-ui.badge>
                                    @endif
                                </x-ui.table-cell>
                                <x-ui.table-cell class="text-right">{{ $remesa->remesa_clientes_count }}</x-ui.table-cell>
                                <x-ui.table-cell class="text-right">{{ $remesa->facturas_count }}</x-ui.table-cell>
                                <x-ui.table-cell>
                                    <div class="flex items-center justify-end gap-1">
                                        @if($remesa->isDraft())
                                            <x-ui.button type="button" size="sm" variant="outline"
                                                wire:click="generate({{ $remesa->id }})"
                                                wire:confirm="¿Generar las facturas para esta remesa?">
                                                <x-lucide-zap class="size-3.5 mr-1" /> Generar
                                            </x-ui.button>
                                        @else
                                            <x-ui.button type="button" size="sm" variant="outline"
                                                wire:click="resetDraft({{ $remesa->id }})"
                                                wire:confirm="¿Marcar la remesa como borrador para poder regenerar?">
                                                <x-lucide-rotate-ccw class="size-3.5 mr-1" /> Regenerar
                                            </x-ui.button>
                                        @endif
                                        <x-ui.button type="button" size="sm" variant="ghost"
                                            @click="$wire.openEdit({{ $remesa->id }}); $dispatch('open-dialog-remesa-form')"
                                            title="Editar clientes">
                                            <x-lucide-pencil class="size-3.5" />
                                        </x-ui.button>
                                        <x-ui.button type="button" size="sm" variant="ghost"
                                            class="text-destructive hover:text-destructive"
                                            wire:click="delete({{ $remesa->id }})"
                                            wire:confirm="¿Eliminar esta remesa?">
                                            <x-lucide-trash-2 class="size-3.5" />
                                        </x-ui.button>
                                    </div>
                                </x-ui.table-cell>
                            </x-ui.table-row>
                        @empty
                            <x-ui.table-row>
                                <x-ui.table-cell colspan="6" class="text-center text-muted-foreground py-12">
                                    <x-lucide-calendar class="size-12 mx-auto mb-4" />
                                    <p>No hay remesas registradas.</p>
                                </x-ui.table-cell>
                            </x-ui.table-row>
                        @endforelse
                    </x-ui.table-body>
                </x-ui.table>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <x-ui.dialog id="remesa-form">
        <x-ui.dialog-content class="sm:max-w-2xl">
            <x-ui.dialog-header>
                <x-ui.dialog-title>{{ $editingId ? 'Editar remesa' : 'Nueva remesa' }}</x-ui.dialog-title>
                <x-ui.dialog-description>Selecciona la fecha y los clientes con facturación recurrente activa.</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.field label="Nombre de la remesa">
                        <x-ui.input wire:model="form.nombre" placeholder="Ej. Remesa julio 2026" />
                        @error('form.nombre') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </x-ui.field>
                    <x-ui.field label="Fecha de emisión">
                        <x-ui.input type="date" wire:model="form.fecha" />
                        @error('form.fecha') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </x-ui.field>
                </div>

                <x-ui.field label="Notas">
                    <textarea wire:model="form.notas" rows="2" placeholder="Notas internas…"
                        class="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] outline-none resize-none"></textarea>
                </x-ui.field>

                <div class="rounded-lg border">
                    <div class="bg-muted/30 px-4 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        Clientes recurrentes
                    </div>
                    <div class="max-h-64 overflow-y-auto p-2 space-y-1">
                        @forelse($this->clientes as $cliente)
                            <label class="flex items-center gap-3 rounded-md px-3 py-2 hover:bg-accent cursor-pointer">
                                <input type="checkbox" wire:model="selectedClientes.{{ $cliente->id }}"
                                    class="rounded border-input size-4 cursor-pointer" />
                                <div class="flex-1">
                                    <div class="text-sm font-medium">{{ $cliente->nombrecorto }}</div>
                                    <div class="text-xs text-muted-foreground">Día de recurrencia: {{ $cliente->recurrencia_dia }}</div>
                                </div>
                            </label>
                        @empty
                            <p class="text-sm text-muted-foreground px-3 py-4 text-center">
                                No hay clientes con facturación recurrente activa.
                            </p>
                        @endforelse
                    </div>
                </div>
                @error('selectedClientes') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                <x-ui.button type="button" variant="outline" size="sm" @click="$dispatch('close-dialog-remesa-form')">Cancelar</x-ui.button>
                <x-ui.button type="button" size="sm" wire:click="save" @click="$dispatch('close-dialog-remesa-form')">
                    <x-lucide-save class="size-3.5 mr-1" /> Guardar remesa
                </x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
</div>
