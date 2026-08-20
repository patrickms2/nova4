@props([
    'events' => [],
    'title' => 'Actividad',
    'eyebrow' => null,
    'limit' => null,
    'newestFirst' => true,
])

@php
    $items = $newestFirst ? array_reverse($events) : $events;

    if ($limit !== null) {
        $items = array_slice($items, 0, (int) $limit);
    }
@endphp

<section {{ $attributes->class('grid gap-4') }}>
    @if ($title || $eyebrow)
        <x-nova.section-heading :eyebrow="$eyebrow" :title="$title" />
    @endif

    @forelse ($items as $index => $event)
        <x-nova.activity-item
            wire:key="nova-activity-{{ $index }}-{{ $event['title'] }}"
            :title="$event['title']"
            :description="$event['description'] ?? null"
            :time="$event['time'] ?? null"
            :context="$event['context'] ?? null"
            :url="$event['url'] ?? null"
            x-bind:class="pulse === '{{ $event['id'] ?? '' }}' ? 'bg-neutral-800/60' : ''"
        />
    @empty
        <x-nova.empty-state
            title="Todavía no hay actividad"
            description="Los eventos aparecerán aquí en cuanto Nova comience a ejecutar."
        />
    @endforelse
</section>
