<x-filament-panels::page>
    @php
        $chatUrl = $this->chatUrl();
        $departments = $this->departments();
    @endphp

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Chat</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Selecciona un departamento para abrir la conversación correcta.
                </p>
            </div>
            @if (! $chatUrl)
                <span class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-300">
                    Chat no disponible
                </span>
            @endif
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($departments as $department)
                <button
                    type="button"
                    wire:click="selectDepartment({{ $department->id }})"
                    class="group flex items-center justify-between rounded-xl border px-4 py-3 text-left transition {{ $this->departmentId === $department->id ? 'border-primary-400 bg-primary-50 dark:border-primary-500/60 dark:bg-primary-950/20' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-gray-600 dark:hover:bg-gray-800' }}"
                >
                    <span>
                        <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $department->name }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Departamento de soporte</span>
                    </span>

                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border text-xs {{ $this->departmentId === $department->id ? 'border-primary-500 bg-primary-100 text-primary-700 dark:border-primary-500 dark:bg-primary-900/40 dark:text-primary-300' : 'border-gray-300 text-gray-400 dark:border-gray-600 dark:text-gray-300' }}">
                        {{ $this->departmentId === $department->id ? '✓' : '→' }}
                    </span>
                </button>
            @empty
                <p class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No hay departamentos activos con chat.
                </p>
            @endforelse
        </div>

        <div class="mt-5">
            <button
                type="button"
                wire:click="openChat"
                @disabled(! $chatUrl)
                class="inline-flex items-center rounded-lg border dark:border-white/15 text-sm px-4 py-2 dark:bg-primary-600"
            >
                Abrir conversación
            </button>

            @if ($this->departmentId)
                <p class="mt-3 inline-flex items-center rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:text-gray-300">
                    Seleccionado: {{ $this->departmentName ?? $departments->firstWhere('id', $this->departmentId)?->name }}
                </p>
            @endif
        </div>
    </section>
</x-filament-panels::page>
