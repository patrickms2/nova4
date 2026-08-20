<x-filament-panels::page>
    @php
        $departments = $this->departments();
        $chatUrl = $this->chatUrl();
    @endphp

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Chat por
                    departamento</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Selecciona un departamento para iniciar conversación.
                </p>
            </div>

            @if (! $chatUrl)
                <span
                    class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-300">
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
                        <span
                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $department->name }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Departamento de soporte</span>
                    </span>

                    <span
                        class="inline-flex h-6 w-6 items-center justify-center rounded-full border text-xs {{ $this->departmentId === $department->id ? 'border-primary-500 bg-primary-100 text-primary-700 dark:border-primary-500 dark:bg-primary-900/40 dark:text-primary-300' : 'border-gray-300 text-gray-400 dark:border-gray-600 dark:text-gray-300' }}">
                        {{ $this->departmentId === $department->id ? '✓' : '→' }}
                    </span>
                </button>
            @empty
                <p class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No hay departamentos activos con chat.
                </p>
            @endforelse
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
            <button
                type="button"
                wire:click="openChat"
                @disabled(! $chatUrl)
                class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Abrir mis chats
            </button>

            @if ($this->departmentId)
                <p class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:text-gray-300">
                    Seleccionado: {{ $departments->firstWhere('id', $this->departmentId)?->name }}
                </p>
            @endif
        </div>

        @if ($this->departmentId)
            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Chats activos
                    en {{ $departments->firstWhere('id', $this->departmentId)?->name }}</h3>
                <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Taxista
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Último mensaje
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Actualizado
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Acción
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                        @forelse ($this->getActiveConversations($this->departmentId) as $chat)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $chat['taxista_name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="line-clamp-1">{{ $chat['last_message'] }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $chat['updated_at'] }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <button
                                        wire:click="joinChat('{{ $chat['id'] }}')"
                                        class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                    >
                                        {{ $chat['is_participant'] ? 'Ir al chat' : 'Unirse al chat' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No hay chats activos para este departamento.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
</x-filament-panels::page>
