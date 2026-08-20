{{-- TICKETS --}}
<div x-data="{ showTicketInfo: false, ticketInfoItem: null }">
    <div class="sticky top-0 z-10 -mx-2 px-2 pt-1 pb-3 backdrop-blur-md ">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="tl-breadcrumb inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2">
                    <a
                        href="{{ route('mobile-portal') }}"
                        class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-white/85 hover:text-white"
                    >
                        <x-heroicon-o-chevron-left class="h-4 w-4"/>
                        Tickets
                    </a>
                </div>
            </div>

            <div class="tl-segment-group flex items-center gap-1.5 shrink-0">
                <button
                    type="button"
                    class="tl-s2-hover inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                    x-bind:class="showTicketsTopFilters ? 'ring-1 ring-red-400/40 text-red-200' : ''"
                    x-bind:aria-label="showTicketsTopFilters ? 'Ocultar opciones' : 'Mostrar opciones'"
                    x-on:click="showTicketsTopFilters = !showTicketsTopFilters"
                >
                    <x-heroicon-o-adjustments-horizontal class="h-4 w-4"/>
                </button>

                <button
                    type="button"
                    class="glass-hover inline-flex h-9 w-9 items-center justify-center rounded-full bg-red-500/20 text-red-100 ring-1 ring-red-500/30"
                    wire:click="mountAction('createTicket')"
                    aria-label="Nuevo ticket"
                >
                    <x-heroicon-o-plus class="h-4 w-4"/>
                </button>
            </div>
        </div>

        <div class=" tl-segment-group mt-3 flex gap-2 overflow-x-auto pb-1" x-show="showTicketsTopFilters"
             x-transition.opacity
             style="display: none;">
            <button type="button" wire:click="toggleTicketsFilter('open')"
                    class="tl-pill  tl-segment {{ $ticketsFilterOpen ? ' tl-segment-active ring-1 ring-red-400/30 bg-red-500/10 text-red-200' : 'tl-pill-zinc' }} px-4 py-1.5 text-xs font-medium">
                Abiertos
            </button>
            <button type="button" wire:click="toggleTicketsFilter('in_progress')"
                    class="tl-pill  tl-segment {{ $ticketsFilterInProgress ? ' tl-segment-active ring-1 ring-amber-400/30 bg-amber-500/10 text-amber-200' : 'tl-pill-zinc' }} px-4 py-1.5 text-xs font-medium">
                En proceso
            </button>
            <button type="button" wire:click="toggleTicketsFilter('all')"
                    class="tl-pill  tl-segment {{ $ticketsFilterAll ? ' tl-segment-active ring-1 ring-white/15 bg-white/10' : 'tl-pill-zinc' }} px-4 py-1.5 text-xs font-medium">
                Todos
            </button>
        </div>

        <p class="mt-2 text-[12px] text-white/60" x-show="showTicketsTopFilters" x-transition.opacity
           style="display: none;">
            {{ count($tickets ?? []) }} tickets
            <span class="text-white/35">·</span>
            <span class="text-white/45">{{ $ticketsFilterAll ? 'Todos' : 'Filtro personalizado' }}</span>
        </p>
    </div>

    <div class="mt-4 space-y-2">
        @if (count($tickets ?? []) === 0)
            <x-portal.card padding="p-8">
                <div class="text-center space-y-2">
                    <x-heroicon-o-ticket class="mx-auto h-10 w-10 text-white/20"/>
                    <p class="text-white/60">Sin tickets para los filtros actuales.</p>
                </div>
            </x-portal.card>
        @else
            @foreach ($tickets as $ticket)
                @php
                    $ticketUrl = (string) ($ticket['url'] ?? '#');
                    $ticketEditUrl = (string) ($ticket['edit_url'] ?? '#');
                    $ticketTitle = (string) ($ticket['title'] ?? 'Ticket');
                    $ticketSubtitle = (string) ($ticket['subtitle'] ?? '—');
                    $ticketDescription = (string) ($ticket['description'] ?? '');
                    $ticketStatus = (string) ($ticket['status'] ?? '');
                    $ticketType = (string) ($ticket['ticket_type'] ?? '');
                    $ticketStatusLabel = (string) ($ticket['status_label'] ?? '');
                    $ticketPriority = (string) ($ticket['priority'] ?? '');
                    $ticketPriorityLabel = (string) ($ticket['priority_label'] ?? '');
                    $ticketPriorityClass = (string) ($ticket['priority_class'] ?? 'text-amber-300');
                    $ticketOpenedAt = (string) ($ticket['opened_at'] ?? '—');
                    $ticketDueAt = $ticket['due_at'] ?? null;
                    $ticketDepartment = (string) ($ticket['department'] ?? '—');
                    $ticketAttachments = $ticket['attachments'] ?? [];
                    $ticketAttachmentUrl = $ticket['attachment_url'] ?? null;
                @endphp
                <button
                    type="button"
                    class="relative z-10 block w-full cursor-pointer text-left pointer-events-auto"
                    x-on:click.stop.prevent="
                            ticketInfoItem = {
                                id: {{ (int) ($ticket['id'] ?? 0) }},
                                title: @js($ticketTitle),
                                subtitle: @js($ticketSubtitle),
                                description: @js($ticketDescription),
                                status: @js($ticketStatus),
                                ticket_type: @js($ticketType),
                                priority: @js($ticketPriority),
                                priority_label: @js($ticketPriorityLabel),
                                priority_class: @js($ticketPriorityClass),
                                opened_at: @js($ticketOpenedAt),
                                due_at: @js($ticketDueAt),
                                department: @js($ticketDepartment),
                                attachments: @js($ticketAttachments),
                                attachment_url: @js($ticketAttachmentUrl),
                                url: @js($ticketUrl),
                                edit_url: @js($ticketEditUrl),
                            };
                            showTicketInfo = true;
                        "
                >
                    <x-portal.row
                        :title="$ticketTitle"
                        :subtitle="$ticketSubtitle !== '' ? $ticketSubtitle : $ticketDescription"
                        :iconBg="'bg-red-500/10 ring-1 ring-red-500/20'"
                    >
                        <x-slot:icon>
                            <x-heroicon-o-ticket class="h-5 w-5 {{ $ticketPriorityClass }}"/>
                        </x-slot:icon>
                        <x-slot:right>
                            <x-portal.badge
                                :color="data_get($ticket, 'badge_color', 'zinc')">{{ $ticketDepartment !== '' ? $ticketDepartment : 'Sin Departamento' }}</x-portal.badge>
                        </x-slot:right>
                        <x-slot:right>
                            <x-portal.badge
                                :color="data_get($ticket, 'badge_color', 'zinc')">{{ $ticketStatusLabel !== '' ? $ticketStatusLabel : $ticketStatus }}</x-portal.badge>
                        </x-slot:right>
                    </x-portal.row>
                </button>
            @endforeach
        @endif
    </div>

    <!-- Ticket Detail Modal -->
    <div
        x-show="showTicketInfo"
        class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm"
        x-on:keydown.escape.window="showTicketInfo = false"
        x-on:click.self="showTicketInfo = false"
        style="display: none;"
    >
        <div class="absolute inset-0 overflow-y-auto px-4 pt-20 pb-6 sm:px-6 sm:pt-24 pointer-events-none">
            <div class="mx-auto w-full max-w-3xl pointer-events-auto" x-on:click.stop>
                <div class="sticky top-2 z-10 -mx-2 px-2 pt-1 pb-2">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="tl-breadcrumb inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2">
                                <span class="text-xs font-semibold uppercase tracking-widest text-white/85">Tickets</span>
                                <span class="text-white/30">›</span>
                                <span class="text-xs font-semibold uppercase tracking-widest text-white/85">Detalle</span>
                            </div>
                        </div>

                        <div class="tl-segment-group tl-interactive tl-interactive-active flex items-center gap-1.5 shrink-0">
                            <a
                                x-show="ticketInfoItem?.edit_url && ticketInfoItem?.edit_url !== '#'"
                                x-bind:href="ticketInfoItem?.edit_url ?? '#'"
                                class="tl-s3 tl-interactive inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                aria-label="Editar ticket"
                            >
                                <x-heroicon-o-pencil-square class="h-4 w-4"/>
                            </a>
                            <a
                                x-show="ticketInfoItem?.attachment_url"
                                x-bind:href="ticketInfoItem?.attachment_url ?? '#'"
                                class="tl-s3 tl-interactive inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                aria-label="Ver adjunto"
                                target="_blank"
                                rel="noopener"
                            >
                                <x-heroicon-o-eye class="h-4 w-4"/>
                            </a>
                            <a
                                x-show="ticketInfoItem?.attachment_url"
                                x-bind:href="ticketInfoItem?.attachment_url ?? '#'"
                                class="tl-s3 tl-interactive inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                aria-label="Descargar adjunto"
                                target="_blank"
                                rel="noopener"
                                download
                            >
                                <x-heroicon-o-arrow-down-tray class="h-4 w-4"/>
                            </a>

                            <button
                                type="button"
                                class="tl-s3 inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                x-on:pointerup="showTicketInfo = false"
                                aria-label="Cerrar"
                            >
                                <x-heroicon-o-chevron-left class="h-4 w-4"/>
                            </button>
                        </div>
                    </div>
                </div>

                <x-portal.card padding="p-6">
                    <div class="flex flex-col gap-4">
                        <div class="min-w-0">
                            <p class="text-xl font-semibold text-white/90 truncate"
                               x-text="ticketInfoItem?.title ?? 'Ticket'"></p>
                            <p class="mt-1 text-sm text-white/45"
                               x-text="ticketInfoItem?.subtitle ?? '—'"></p>
                        </div>

                        <div class="tl-inner-surface rounded-3xl p-5 sm:p-6">
                            <p class="text-white/70 text-sm font-semibold">Resumen del ticket</p>

                            <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-white/40 text-[11px] uppercase tracking-widest">ID</dt>
                                    <dd class="mt-1 text-white/85 font-semibold"
                                        x-text="ticketInfoItem?.id ?? '—'"></dd>
                                </div>

                                <div>
                                    <dt class="text-white/40 text-[11px] uppercase tracking-widest">Estado</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-[12px] font-medium"
                                              x-bind:class="ticketInfoItem?.status === 'abierto' ? 'border-green-400/30 bg-green-500/10 text-green-200' : ticketInfoItem?.status === 'en_progreso' ? 'border-amber-400/30 bg-amber-500/10 text-amber-200' : 'border-gray-400/30 bg-gray-500/10 text-gray-200'"
                                              x-text="ticketInfoItem?.status_label ?? '—'"></span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-white/40 text-[11px] uppercase tracking-widest">Prioridad</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-[12px] font-medium"
                                              x-bind:class="ticketInfoItem?.priority === 'alta' ? 'border-red-400/30 bg-red-500/10 text-red-200' : ticketInfoItem?.priority === 'media' ? 'border-amber-400/30 bg-amber-500/10 text-amber-200' : 'border-gray-400/30 bg-gray-500/10 text-gray-200'"
                                              x-text="ticketInfoItem?.priority_label ?? ticketInfoItem?.priority ?? '—'"></span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-white/40 text-[11px] uppercase tracking-widest">Abierto</dt>
                                    <dd class="mt-1 text-blue-200 font-semibold"
                                        x-text="ticketInfoItem?.opened_at ?? ticketInfoItem?.subtitle ?? '—'"></dd>
                                </div>
                                <div>
                                    <dt class="text-white/40 text-[11px] uppercase tracking-widest">Departamento</dt>
                                    <dd class="mt-1 text-white/80"
                                        x-text="ticketInfoItem?.department ?? '—'"></dd>
                                </div>
                                <div>
                                    <dt class="text-white/40 text-[11px] uppercase tracking-widest">Tipo de Ticket</dt>
                                    <dd class="mt-1 text-white/80"
                                        x-text="ticketInfoItem?.ticket_type ?? '—'"></dd>
                                </div>

                                <div>
                                    <dt class="text-white/40 text-[11px] uppercase tracking-widest">Vence</dt>
                                    <dd class="mt-1 text-white/80"
                                        x-text="ticketInfoItem?.due_at ?? '—'"></dd>
                                </div>

                                <div class="col-span-2">
                                    <dt class="text-white/40 text-[11px] uppercase tracking-widest">Descripción</dt>
                                    <dd class="mt-1 text-white/70 text-sm whitespace-pre-line break-words"
                                       x-text="ticketInfoItem?.description ?? '—'"></dd>
                                </div>

                                <div class="col-span-2">
                                    <dt class="text-white/40 text-[11px] uppercase tracking-widest">Adjuntos</dt>
                                    <dd class="mt-2">
                                        <div
                                            class="flex flex-col gap-2"
                                            x-show="(ticketInfoItem?.attachments ?? []).length > 0"
                                            style="display: none;"
                                        >
                                            <template x-for="file in (ticketInfoItem?.attachments ?? [])" :key="file.url">
                                                <a
                                                    class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/80 hover:text-white"
                                                    x-bind:href="file.url"
                                                    target="_blank"
                                                    rel="noopener"
                                                >
                                                    <x-heroicon-o-document-text class="h-4 w-4 text-white/50"/>
                                                    <span class="truncate" x-text="file.name"></span>
                                                </a>
                                            </template>
                                        </div>
                                        <p
                                            class="text-white/40 text-sm"
                                            x-show="(ticketInfoItem?.attachments ?? []).length === 0"
                                        >
                                            Sin adjuntos
                                        </p>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </x-portal.card>
            </div>
        </div>
    </div>
</div>
