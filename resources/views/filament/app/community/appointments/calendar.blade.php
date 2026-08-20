<x-filament-panels::page>
    @php($calendar = $this->calendar())
    <div class="flex items-center justify-between gap-4">
        <x-filament::button color="gray" tag="a" :href="'?month='.$calendar['previous']">Anterior</x-filament::button>
        <h2 class="text-xl font-semibold capitalize">{{ $calendar['label'] }}</h2>
        <x-filament::button color="gray" tag="a" :href="'?month='.$calendar['next']">Siguiente</x-filament::button>
    </div>
    <div class="grid grid-cols-7 gap-px overflow-hidden rounded-xl bg-gray-200 ring-1 ring-gray-200 dark:bg-gray-700 dark:ring-gray-700">
        @foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $day)<div class="bg-gray-50 p-2 text-center text-xs font-semibold dark:bg-gray-800">{{ $day }}</div>@endforeach
        @foreach ($calendar['weeks'] as $week) @foreach ($week as $day)
            <div class="min-h-32 bg-white p-2 dark:bg-gray-900 {{ $day['inMonth'] ? '' : 'opacity-50' }}" wire:key="day-{{ $day['date']->format('Y-m-d') }}">
                <div class="mb-2 text-sm font-medium">{{ $day['date']->day }}</div>
                @foreach ($day['items'] as $item)<a class="mb-1 block rounded-lg bg-primary-50 p-2 text-xs text-primary-700 dark:bg-primary-950 dark:text-primary-300" href="{{ \App\Filament\App\Community\Resources\CommunityAppointments\CommunityAppointmentResource::getUrl('view', ['record' => $item]) }}"><strong>{{ $item->starts_at->format('H:i') }}</strong> {{ $item->person?->display_name ?? $item->title }}</a>@endforeach
            </div>
        @endforeach @endforeach
    </div>
</x-filament-panels::page>
