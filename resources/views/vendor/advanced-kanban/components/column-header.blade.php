@php use Filament\Support\Enums\IconSize;
@endphp
<div>
    @php
        use Flux\Flux;
      use App\Enums\CitasTipos;
      use App\Models\Taxi\Departamento;
      use App\Models\Taxi\TipoCitas;
          $types = TipoCitas::query()
          ->whereHas(
              'citas')
          ->withCount(['citas' => fn ($q) => $q

              ->orderByDesc('appointment_date')
              ->orderByDesc('id'),
          ])
          ->with(['citas' => fn ($q) => $q

              ->select(['id', 'usuario_id' ,
                  'departamento_id',
                  'tipo_id',
                  'appointment_time' ,
                  'appointment_date',
                  'title' ,
                  'appointment_type' ,
                  'slot_id' ,
                  'status' ,
                  'notes'])
              ->orderByDesc('appointment_date')
              ->orderByDesc('id')
              ->take(10),
          ])
          ->get(['id', 'nombre', 'observaciones', 'slug','icono','color','estado']);
    @endphp
    @props([
        'column' => null,
        'id' => null,
        'status' => null,
        'actions' => [],
    ])
    <div>
        <div class="ak:flex ak:items-center ak:justify-between"
             x-data="{ visible: false, toggley(){this.visible =! this.visible},open{{ $id }}: false, get isOpen{{ $id }}() {return this.open{{ $id }} == this.open ? true : false; }, toggle{{ $id }}() {this.open{{ $id }} = !this.open{{ $id }}; },}"

        >
            <!-- <div class="w-full flex ak:items-center justify-between gap-3">
                <div class="kanban-column-label kanban-column-label w-full justify-between "  :class="!open ? 'flex-col' : 'flex-row'"

                >

                            <flux:icon icon="chevron-right"  @click="toggley()"  size="md" square class="shrink-0 [:where(&amp;)]:size-8 size-8"  variant="micro"  x-show="visible" />
                            <flux:icon icon="chevron-down"  @click="toggley()"  size="md" square class="shrink-0 [:where(&amp;)]:size-8 size-8"  variant="micro"  x-show="!visible" /> -->


            <!-- <button
                    class="  px-4 h-8 rounded-md bg-zinc-100 w-full justify-between pb-4 flex max-h-6  items-start gap-2"
                    icon="tag"
                    icon:variant="micro"
                > -->


            <div class="ak:space-y-1">
                <div class="ak:flex ak:items-center ak:gap-2">
                    <x-filament::icon :icon="$column->getIcon()"
                                      :color="$column->getIconColor()"
                                      :size="$column->getIconSize()"
                    />
                    <h3 class="kanban-column-title">
                        {{ $column->getLabel() ?? $column->getStatus() }}
                    </h3>
                    <x-filament::badge
                        color="primary"
                        size="sm"
                    >
                        {{ $this->getKanban()->getTotalCount($column->getStatus()) ?? 0 }}
                    </x-filament::badge>

                </div>

                <p class="kanban-column-description ak:text-xs ak:text-gray-500">
                    {{ $column->getDescription() }}
                </p>
            </div>
            <!--  <flux:button variant=primary class="  mt-2 mr-2 text-red-100 " size="xs"
                                                      color="red" @click="toggley()" x-show="visible"
                                                      icon="chevron-down"/>
                                         <flux:button class="  mt-2 mr-2" size="xs" variant="primary" color="red"
                                                      @click="toggley()" x-show="!visible" icon="x-mark"/>
                                           </div>


                                       </button>

                       </div>  -->


        </div>
        <div class="ak:flex ak:items-center ak:gap-2">
            @foreach($actions as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>
    @script
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dropdown', () => ({
                view: 'list',
                tipo: 'all',
                open: true,
                togglex() {
                    this.open = !this.open
                },
                get isOpen() {
                    return this.open
                },
                cambiaTipo() {
                    alert(this.tipo);
                },
                @foreach($types as $id => $label)
                open{{ $id }}: false,
                get isOpen{{ $id }}() {
                    return this.open{{ $id }} == this.open ? true : false;
                },
                toggle{{ $id }}() {
                    this.open{{ $id }} = !this.open{{ $id }};
                },
                @endforeach
            }))

        });
    </script>
    @endscript
</div>
