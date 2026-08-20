<x-filament-panels::page>
    <div class="grid gap-4 xl:grid-cols-4">
        @foreach ($this->columns() as $column)
            <section class="rounded-xl bg-gray-50 p-3 ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-700" wire:key="column-{{ $column['status'] }}">
                <h2 class="mb-3 flex items-center justify-between font-semibold"><span>{{ $column['label'] }}</span><span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs dark:bg-gray-700">{{ $column['items']->count() }}</span></h2>
                <div class="space-y-3">@foreach ($column['items'] as $item)<a class="block rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700" href="{{ \App\Filament\App\Community\Resources\CommunityAppointments\CommunityAppointmentResource::getUrl('view', ['record' => $item]) }}"><div class="font-medium">{{ $item->title }}</div><div class="mt-1 text-xs text-gray-500">{{ $item->starts_at->format('d/m/Y H:i') }} · {{ $item->person?->display_name ?? 'Sin propietario' }}</div><div class="mt-1 text-xs">{{ $item->community?->name }}</div></a>@endforeach</div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
