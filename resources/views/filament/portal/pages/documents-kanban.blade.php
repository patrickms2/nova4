<x-filament-panels::page>
    @php($columns = $this->getKanbanColumns())

    <div class="portal-kanban">
        @forelse ($columns as $column => $items)
            <section class="portal-kanban__column">
                <header class="portal-kanban__header">
                    <h3>{{ $column }}</h3>
                    <span class="portal-kanban__count">{{ $items->count() }}</span>
                </header>

                <div class="portal-kanban__cards">
                    @forelse ($items as $doc)
                        <article class="portal-kanban__card">
                            <p class="portal-kanban__title">{{ $doc->file_name ?: 'Sin archivo' }}</p>
                            <p class="portal-kanban__meta">{{ $doc->created_at?->format('d/m/Y H:i') ?: '-' }}</p>
                            <p class="portal-kanban__meta">{{ $doc->departamento?->nombre ?? 'Sin departamento' }}</p>
                        </article>
                    @empty
                        <p class="portal-kanban__empty">Sin documentos</p>
                    @endforelse
                </div>
            </section>
        @empty
            <p class="portal-kanban__empty">No hay documentos para mostrar.</p>
        @endforelse
    </div>
</x-filament-panels::page>

