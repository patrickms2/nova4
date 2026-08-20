<div class="p-4 max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('comunigest.admin.communities') }}" class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
            <x-lucide-arrow-left class="size-4 mr-1" /> Volver a comunidades
        </a>
        @if($community->status === 'active')
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700">Activa</span>
        @else
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">Inactiva</span>
        @endif
    </div>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-foreground">{{ $community->name }}</h1>
        <p class="text-sm text-muted-foreground mt-1">{{ $community->address ?? 'Sin dirección' }}</p>
        <p class="text-xs text-muted-foreground font-mono mt-1">{{ $community->code }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <x-ui.card>
            <x-ui.card-header class="pb-2">
                <x-ui.card-title class="text-sm flex items-center gap-2">
                    <x-lucide-user class="size-4 text-blue-600" /> Contacto
                </x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content class="space-y-1">
                <p class="text-sm font-medium">{{ $community->contact_name ?? '—' }}</p>
                @if($community->contact_phone)
                    <a href="tel:{{ $community->contact_phone }}" class="text-sm text-blue-600 hover:underline inline-flex items-center gap-1">
                        <x-lucide-phone class="size-3.5" /> {{ $community->contact_phone }}
                    </a>
                @else
                    <p class="text-sm text-muted-foreground">—</p>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header class="pb-2">
                <x-ui.card-title class="text-sm flex items-center gap-2">
                    <x-lucide-sticky-note class="size-4 text-amber-600" /> Notas
                </x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ $community->notes ?? 'Sin notas' }}</p>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <x-ui.card class="mb-6">
        <x-ui.card-header class="pb-2 flex items-center justify-between">
            <x-ui.card-title class="text-sm flex items-center gap-2">
                <x-lucide-calendar class="size-4 text-purple-600" /> Planes
            </x-ui.card-title>
            <x-ui.button type="button" size="sm" wire:click="openPlanModal(null)">
                <x-lucide-plus class="size-3.5" /> Nuevo plan
            </x-ui.button>
        </x-ui.card-header>
        <x-ui.card-content>
            @if ($community->plans->isEmpty())
                <p class="text-sm text-muted-foreground">No hay planes configurados.</p>
            @else
                <div class="space-y-3">
                    @foreach ($community->plans as $plan)
                        <div class="border border-neutral-200 rounded-lg p-3" wire:key="plan-{{ $plan->id }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold">Vigente desde {{ $plan->valid_from?->format('d/m/Y') ?? '—' }}</p>
                                    @if($plan->status === 'active')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">Activo</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">Borrador</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    <x-ui.button type="button" size="sm" variant="outline" wire:click="openPlanModal({{ $plan->id }})" class="size-7 p-0">
                                        <x-lucide-pencil class="size-4" />
                                    </x-ui.button>
                                    <x-ui.button type="button" size="sm" variant="outline" wire:click="deletePlan({{ $plan->id }})" wire:confirm="¿Eliminar plan?" class="size-7 p-0 text-destructive">
                                        <x-lucide-trash-2 class="size-4" />
                                    </x-ui.button>
                                </div>
                            </div>
                            @if($plan->valid_until)
                                <p class="text-xs text-muted-foreground mt-1">Hasta {{ $plan->valid_until?->format('d/m/Y') }}</p>
                            @endif
                            @if($plan->items->isNotEmpty())
                                <ul class="mt-2 space-y-1">
                                    @foreach ($plan->items as $item)
                                        <li class="text-sm flex items-start gap-2">
                                            <x-lucide-check class="size-4 text-emerald-600 shrink-0 mt-0.5" />
                                            <span>
                                                {{ $item->title }}
                                                @if($item->days->isNotEmpty())
                                                    <span class="text-muted-foreground">({{ $item->days->pluck('day_of_week')->implode(', ') }})</span>
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-muted-foreground mt-1">Sin ítems</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card-content>
    </x-ui.card>

    <x-ui.card>
        <x-ui.card-header class="pb-2">
            <x-ui.card-title class="text-sm flex items-center gap-2">
                <x-lucide-clipboard-list class="size-4 text-slate-600" /> Órdenes recientes
            </x-ui.card-title>
        </x-ui.card-header>
        <x-ui.card-content>
            @if ($community->workOrders->isEmpty())
                <p class="text-sm text-muted-foreground">No hay órdenes de trabajo.</p>
            @else
                <div class="space-y-3">
                    @foreach ($community->workOrders as $order)
                        <a href="{{ route('comunigest.admin.work-orders') }}" class="block border border-neutral-200 rounded-lg p-3 hover:bg-muted/50 transition">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold">{{ $order->code }}</p>
                                <span class="text-xs text-muted-foreground">{{ $order->work_date?->format('d/m/Y') }}</span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">{{ $order->tasks->pluck('title')->implode(', ') ?: 'Sin tareas' }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-ui.card-content>
    </x-ui.card>

    {{-- Plan modal --}}
    <x-ui.dialog wire:model="showPlanModal">
        <x-ui.dialog-content class="sm:max-w-3xl max-h-[90vh] overflow-y-auto">
            <x-ui.dialog-header>
                <x-ui.dialog-title>{{ $planId ? 'Editar plan' : 'Nuevo plan' }}</x-ui.dialog-title>
                <x-ui.dialog-description>{{ $community->name }}</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4">
                <div class="flex items-center gap-2 mb-4">
                    @foreach([1 => 'Datos plan', 2 => 'Ítems', 3 => 'Días'] as $step => $label)
                        <button type="button" wire:click="$set('planStep', {{ $step }})" class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition {{ $planStep === $step ? 'bg-foreground text-background' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                @if($planStep === 1)
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.field label="Vigente desde">
                            <x-ui.input type="date" wire:model="planValidFrom" />
                            @error('planValidFrom') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                        </x-ui.field>

                        <x-ui.field label="Vigente hasta (opcional)">
                            <x-ui.input type="date" wire:model="planValidUntil" />
                            @error('planValidUntil') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                        </x-ui.field>

                        <x-ui.field label="Estado">
                            <x-ui.select native wire:model="planStatus" class="w-full">
                                <option value="draft">Borrador</option>
                                <option value="active">Activo</option>
                            </x-ui.select>
                            @error('planStatus') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                        </x-ui.field>
                    </div>
                @elseif($planStep === 2)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold">Ítems del plan</h3>
                            <x-ui.button type="button" size="sm" variant="outline" wire:click="addPlanItem">
                                <x-lucide-plus class="size-3.5" /> Añadir ítem
                            </x-ui.button>
                        </div>

                        <div class="space-y-3">
                            @foreach($planItems as $index => $item)
                                <div class="border border-neutral-200 rounded-lg p-3" wire:key="plan-item-{{ $index }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-muted-foreground">Ítem #{{ $index + 1 }}</span>
                                        @if(count($planItems) > 1)
                                            <x-ui.button type="button" size="sm" variant="ghost" wire:click="removePlanItem({{ $index }})" class="size-6 p-0 text-destructive">
                                                <x-lucide-x class="size-3.5" />
                                            </x-ui.button>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 mb-2">
                                        <x-ui.field label="Tipo de tarea">
                                            <x-ui.select native wire:model="planItems.{{ $index }}.categoryId" class="w-full">
                                                <option value="">—</option>
                                                @foreach($workCategories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            </x-ui.select>
                                            @error('planItems.'.$index.'.categoryId') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                                        </x-ui.field>

                                        <x-ui.field label="Tarea del tipo seleccionado">
                                            <x-ui.select native wire:model="planItems.{{ $index }}.catalogId" wire:change="selectCatalog({{ $index }})" class="w-full">
                                                <option value="">—</option>
                                                @php $selectedCategory = $workCategories->firstWhere('id', (int) $item['categoryId']); @endphp
                                                @foreach($selectedCategory?->catalogItems ?? [] as $catalog)
                                                    <option value="{{ $catalog->id }}">{{ $catalog->title }}</option>
                                                @endforeach
                                            </x-ui.select>
                                            @error('planItems.'.$index.'.catalogId') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                                        </x-ui.field>
                                    </div>

                                    <x-ui.field label="Título">
                                        <x-ui.input wire:model="planItems.{{ $index }}.title" placeholder="Título del ítem" />
                                        @error('planItems.'.$index.'.title') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                                    </x-ui.field>

                                    <div class="grid grid-cols-2 gap-3 mt-2">
                                        <x-ui.field label="Instrucciones">
                                            <textarea wire:model="planItems.{{ $index }}.instructions" rows="2" class="w-full px-3 py-2 text-xs border rounded-md border-neutral-200 bg-background resize-none"></textarea>
                                        </x-ui.field>

                                        <x-ui.field label="Requisitos">
                                            <textarea wire:model="planItems.{{ $index }}.requirements" rows="2" class="w-full px-3 py-2 text-xs border rounded-md border-neutral-200 bg-background resize-none"></textarea>
                                        </x-ui.field>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($planItems as $index => $item)
                            <div class="border border-neutral-200 rounded-lg p-3" wire:key="plan-days-{{ $index }}">
                                <p class="text-sm font-semibold">{{ $item['title'] ?: 'Ítem #'.($index + 1) }}</p>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach(['L','M','X','J','V','S','D'] as $dayLabel)
                                        @php $dayValue = $loop->iteration; @endphp
                                        <label class="inline-flex items-center gap-1.5 text-sm px-2 py-1 border rounded-md hover:bg-muted cursor-pointer">
                                            <input type="checkbox" wire:model="planItems.{{ $index }}.days" value="{{ $dayValue }}" class="rounded border-neutral-300">
                                            {{ $dayLabel }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                @if($planStep > 1)
                    <x-ui.button type="button" size="sm" variant="outline" wire:click="prevStep">Anterior</x-ui.button>
                @endif

                @if($planStep < 3)
                    <x-ui.button type="button" size="sm" wire:click="nextStep">Siguiente</x-ui.button>
                @else
                    <x-ui.button type="button" size="sm" wire:click="savePlan">
                        <x-lucide-check class="size-3.5" /> Guardar plan
                    </x-ui.button>
                @endif
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
</div>
