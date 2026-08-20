<x-filament-panels::page>
    @php($calendar = $this->calendar())

    <div class="flex items-center justify-between gap-4">
        <x-filament::button color="gray" tag="a" :href="'?month='.$calendar['previous']">Anterior</x-filament::button>
        <div class="text-center"><h2 class="text-xl font-semibold capitalize">{{ $calendar['label'] }}</h2><p class="text-sm text-gray-500 dark:text-gray-400">Órdenes generadas por los planes de mantenimiento</p></div>
        <x-filament::button color="gray" tag="a" :href="'?month='.$calendar['next']">Siguiente</x-filament::button>
    </div>

    <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="grid min-w-[900px] grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700">
            @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $day)
                <div class="bg-gray-50 p-2 text-center text-xs font-semibold dark:bg-gray-800">{{ $day }}</div>
            @endforeach

            @foreach ($calendar['weeks'] as $week)
                @foreach ($week as $day)
                    <div wire:key="community-calendar-{{ $day['date']->format('Y-m-d') }}" class="min-h-36 bg-white p-2 dark:bg-gray-900 {{ $day['inMonth'] ? '' : 'opacity-45' }}">
                        <div class="mb-2 text-sm font-medium">{{ $day['date']->day }}</div>
                        <div class="grid gap-1.5">
                            @foreach ($day['orders'] as $order)
                                <a href="{{ \App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource::getUrl('view', ['record' => $order]) }}" class="block rounded-lg border border-primary-200 bg-primary-50 p-2 text-xs text-primary-800 transition hover:-translate-y-0.5 hover:border-primary-400 hover:shadow-sm dark:border-primary-800 dark:bg-primary-950 dark:text-primary-200">
                                    <strong class="block truncate">{{ $order->code }}</strong>
                                    <span class="mt-1 block truncate">{{ $order->plan?->name ?? 'Orden manual' }}</span>
                                    <span class="mt-1 block text-[10px] opacity-70">{{ $order->pending_tasks_count }}/{{ $order->tasks_count }} pendientes · {{ $order->status }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
