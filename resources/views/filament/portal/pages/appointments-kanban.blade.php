<x-filament-panels::page>
    @php($columns = $this->getKanbanColumns())

    <div class="portal-kanban">
        @foreach ($columns as $column => $items)
            <section class="portal-kanban__column">
                <header class="portal-kanban__header">
                    <h3>{{ ucfirst($column) }}</h3>
                    <span class="portal-kanban__count">{{ $items->count() }}</span>
                </header>

                <div class="portal-kanban__cards">
                    @forelse ($items as $meeting)
                        <article class="portal-kanban__card">
                            <p class="portal-kanban__title">{{ $meeting->title ?: 'Sin motivo' }}</p>
                            <p class="portal-kanban__meta">
                                {{ $meeting->scheduled_start_at?->format('d/m/Y H:i') ?: '-' }}
                                @if($meeting->scheduled_end_at)
                                    · {{ $meeting->scheduled_end_at->format('H:i') }}
                                @endif
                            </p>
                            <p class="portal-kanban__meta">
                                {{ $meeting->tipo?->nombre ?? 'Sin tipo' }} · {{ $meeting->department?->name ?? 'Sin departamento' }}
                            </p>
                        </article>
                    @empty
                        <p class="portal-kanban__empty">Sin citas</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>

