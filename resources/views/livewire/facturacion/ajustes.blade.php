<div x-data="selectPrimary('ajustes')" class="bg-background">
    <header class="bg-background sticky top-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b px-4 lg:px-6">
        <x-ui.sidebar-trigger class="-ml-1" />
        <x-ui.separator orientation="vertical" class="mr-2 data-[orientation=vertical]:h-4" />
        <h1 class="text-base font-medium">Ajustes</h1>
    </header>

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl w-full mx-auto">
        <x-ui.tabs value="contador">
            <x-ui.tabs-list variant="underline" class="w-full">
                <x-ui.tabs-trigger value="contador">Contador de facturas</x-ui.tabs-trigger>
                <x-ui.tabs-trigger value="conceptos">Conceptos</x-ui.tabs-trigger>
<x-ui.tabs-trigger value="categorias">Categorías</x-ui.tabs-trigger>

            </x-ui.tabs-list>

            <x-ui.tabs-content value="contador">
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>Contador de facturas</x-ui.card-title>
                        <x-ui.card-description>
                            Establece el número del último contador para cada año. La siguiente factura se generará con el número siguiente.
                        </x-ui.card-description>
                    </x-ui.card-header>

                    <x-ui.card-content class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-ui.field label="Año">
                                <x-ui.input type="number" wire:model.live="ano" min="2000" />
                            </x-ui.field>

                            <x-ui.field label="Último contador">
                                <x-ui.input type="number" wire:model="contador" min="0" />
                            </x-ui.field>
                        </div>

                        <div class="rounded-md bg-muted px-4 py-3 text-sm text-muted-foreground">
                            La siguiente factura del año {{ $ano }} usará el número
                            <strong class="text-foreground">{{ str_pad((string) ($contador + 1), 5, '0', STR_PAD_LEFT) }}_{{ $ano }}</strong>.
                        </div>

                        @error('ano') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                        @error('contador') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                    </x-ui.card-content>

                    <x-ui.card-footer class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="outline" size="sm" wire:click="loadContador">
                            <x-lucide-rotate-ccw class="size-3.5 mr-1" /> Restaurar
                        </x-ui.button>
                        <x-ui.button type="button" size="sm" wire:click="save">
                            <x-lucide-save class="size-3.5 mr-1" /> Guardar
                        </x-ui.button>
                    </x-ui.card-footer>
                </x-ui.card>
            </x-ui.tabs-content>

            <x-ui.tabs-content value="conceptos">
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>Conceptos</x-ui.card-title>
                        <x-ui.card-description>
                            Edita, asocia a clientes y gestiona los conceptos disponibles para facturar.
                        </x-ui.card-description>
                    </x-ui.card-header>

                    <x-ui.card-content class="space-y-4">
                        <div class="flex flex-wrap items-end gap-3">
                            <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Buscar concepto…" class="w-64">
                                <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
                            </x-ui.input>
                            <x-ui.select native size="sm" wire:model.live="clienteFilter" class="w-56">
                                <option value="">Todos los clientes</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombretotal }}</option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.button type="button" size="sm" wire:click="nuevoConcepto">
                                <x-lucide-plus class="size-4" />
                                Nuevo concepto
                            </x-ui.button>
                        </div>

                <div class="border rounded-lg overflow-hidden">
                    <x-ui.table>
                        <x-ui.table-header>
                            <x-ui.table-row>
                                <x-ui.table-head>Concepto</x-ui.table-head>
                                <x-ui.table-head>Cliente</x-ui.table-head>
                                <x-ui.table-head class="text-right">Precio</x-ui.table-head>
                                <x-ui.table-head class="text-center">Recurrente</x-ui.table-head>
                                <x-ui.table-head class="w-16"></x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @forelse($conceptos as $concepto)
                                <x-ui.table-row wire:key="concepto-{{ $concepto->id }}">
                                    <x-ui.table-cell>
                                        <div class="font-medium text-sm">{{ $concepto->concepto }}</div>
                                        @if($concepto->categoria)
                                            <div class="text-[10px] text-muted-foreground">{{ $concepto->categoria }}</div>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        {{ optional($concepto->cliente)->nombretotal ?? '—' }}
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-right tabular-nums text-sm">
                                        {{ number_format($concepto->precio, 2, ',', '.') }} €
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-center">
                                        @if($concepto->recurrente)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sí</span>
                                        @else
                                            <span class="text-xs text-muted-foreground">—</span>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell>
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.button type="button" size="sm" variant="ghost"
                                                @click="$wire.openEditConcepto({{ $concepto->id }}); $dispatch('open-dialog-concepto-form')"
                                                title="Editar concepto">
                                                <x-lucide-pencil class="size-3.5" />
                                            </x-ui.button>
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
                                    <x-ui.table-cell colspan="5" class="text-center text-muted-foreground py-8 text-sm">
                                        Sin conceptos registrados.
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @endforelse
                        </x-ui.table-body>
                    </x-ui.table>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    </x-ui.tabs-content>


    <x-ui.tabs-content value="categorias">
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>Categorías</x-ui.card-title>
                        <x-ui.card-description>
                            Edita, asocia a clientes y gestiona categorías disponibles para gastos.
                        </x-ui.card-description>
                    </x-ui.card-header>

                    <x-ui.card-content class="space-y-4">
                        <div class="flex flex-wrap items-end gap-3">
                            <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Buscar concepto…" class="w-64">
                                <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
                            </x-ui.input>
                            <x-ui.select native size="sm" wire:model.live="clienteFilter" class="w-56">
                                <option value="">Todos los clientes</option>
                                @foreach($users as $c)
                                    <option value="{{ $c->id }}">{{ $c->first_name }}</option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.button size="sm" @click="$wire.nuevaCategoria()">
                <x-lucide-plus class="size-4" />
                Nueva Categoría
            </x-ui.button>
                        </div>

                <div class="border rounded-lg overflow-hidden">
                    <x-ui.table>
                        <x-ui.table-header>
                            <x-ui.table-row>
                                <x-ui.table-head>Categoría</x-ui.table-head>
                                <x-ui.table-head>Tipo</x-ui.table-head>
                                <x-ui.table-head class="w-16"></x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @forelse($categorias as $categoria)
                                <x-ui.table-row wire:key="categoria-{{ $categoria->id }}"
@dblclick="$wire.openEditCategoria({{ $categoria->id }}); $dispatch('open-dialog-categoria-form')
">
                                    <x-ui.table-cell>
                                        <div class="font-medium text-sm">{{ $categoria->name }}</div>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell>
                                        @if($categoria->type == 'income')
<span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Entrada</span>
@else
<span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Gasto</span>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell>
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.button type="button" size="sm" variant="ghost"
                                                @click="$wire.openEditCategoria({{ $categoria->id }}); $dispatch('open-dialog-categoria-form')"
                                                title="Editar categoría">
                                                <x-lucide-pencil class="size-3.5" />
                                            </x-ui.button>
                                            <x-ui.button type="button" size="sm" variant="ghost"
                                                class="text-destructive hover:text-destructive"
                                                wire:click="deleteCategoria({{ $categoria->id }})"
                                                wire:confirm="¿Eliminar esta categoría?">
                                                <x-lucide-trash-2 class="size-3.5" />
                                            </x-ui.button>
                                        </div>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @empty
                                <x-ui.table-row>
                                    <x-ui.table-cell colspan="5" class="text-center text-muted-foreground py-8 text-sm">
                                        Sin categorías registradas.
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @endforelse
                        </x-ui.table-body>
                    </x-ui.table>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    </x-ui.tabs-content>
</x-ui.tabs>
</div>


<x-ui.dialog id="categoria-newform">
        <x-ui.dialog-content class="sm:max-w-lg">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Crear categoría</x-ui.dialog-title>
                <x-ui.dialog-description>Crea una categoría y su asociación al usuario.</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-ui.field label="Usuario">
                        <x-ui.select native wire:model="categoriaForm.user_id" class="w-full">
                            <option value="">— Selecciona usuario —</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->first_name }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('categoriaForm.user_id') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </x-ui.field>
                </div>
                <div class="col-span-2">
                    <x-ui.field label="Name">
                        <x-ui.input wire:model="categoriaForm.name" placeholder="Nombre de la categoría" />
                        @error('categoriaForm.name') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </x-ui.field>
                </div>
                <x-ui.field label="Type">
                    <x-ui.select native wire:model="categoriaForm.type" class="w-full">
                        <option value="">Sin tipo</option>
                            <option value="income">Entrada</option>
<option value="expense">Gasto</option>                            
                    </x-ui.select>
                </x-ui.field>
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                <x-ui.button type="button" variant="outline" size="sm" @click="$dispatch('close-dialog-categoria-form')">Cancelar</x-ui.button>
                <x-ui.button type="button" size="sm" wire:click="createCategoria" @click="$dispatch('close-dialog-categoria-newform')">
                    <x-lucide-save class="size-3.5 mr-1" /> Guardar
                </x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>

 <x-ui.dialog id="categoria-form">
        <x-ui.dialog-content class="sm:max-w-lg">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Editar categoría</x-ui.dialog-title>
                <x-ui.dialog-description>Modifica la categoría y su asociación al usuario.</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-ui.field label="Usuario">
                        <x-ui.select native wire:model="categoriaForm.user_id" class="w-full">
                            <option value="">— Selecciona usuario —</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->first_name }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('categoriaForm.user_id') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </x-ui.field>
                </div>
                <div class="col-span-2">
                    <x-ui.field label="Name">
                        <x-ui.input wire:model="categoriaForm.name" placeholder="Nombre de la categoría" />
                        @error('categoriaForm.name') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </x-ui.field>
                </div>
                <x-ui.field label="Type">
                    <x-ui.select native wire:model="categoriaForm.type" class="w-full">
                        <option value="">Sin tipo</option>
                            <option value="income">Entrada</option>
<option value="expense">Gasto</option>                            
                    </x-ui.select>
                </x-ui.field>
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                <x-ui.button type="button" variant="outline" size="sm" @click="$dispatch('close-dialog-categoria-form')">Cancelar</x-ui.button>
                <x-ui.button type="button" size="sm" wire:click="saveCategoria" @click="$dispatch('close-dialog-categoria-form')">
                    <x-lucide-save class="size-3.5 mr-1" /> Guardar
                </x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>

    <x-ui.dialog id="concepto-form">
        <x-ui.dialog-content class="sm:max-w-lg">
            <x-ui.dialog-header>
                <x-ui.dialog-title>{{ $editingConceptoId ? 'Editar concepto' : 'Crear concepto' }}</x-ui.dialog-title>
                <x-ui.dialog-description>{{ $editingConceptoId ? 'Modifica el concepto y su asociación al cliente.' : 'Crea un nuevo concepto asociado a un cliente.' }}</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-ui.field label="Cliente">
                        <x-ui.select native wire:model="conceptoForm.cliente_id" class="w-full">
                            <option value="">— Selecciona cliente —</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}">{{ $c->nombretotal }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('conceptoForm.cliente_id') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </x-ui.field>
                </div>
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
                        <span class="text-sm">Incluir en remesas</span>
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
                <x-ui.button type="button" variant="outline" size="sm" @click="$dispatch('close-dialog-concepto-form')">Cancelar</x-ui.button>
                <x-ui.button type="button" size="sm" wire:click="saveConcepto" @click="$dispatch('close-dialog-concepto-form')">
                    <x-lucide-save class="size-3.5 mr-1" /> Guardar
                </x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
</div>
