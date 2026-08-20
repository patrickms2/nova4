<div
    x-show="showAnnouncements"
    x-transition.opacity
    class="fixed inset-0 z-[70]"
>
    <div class="absolute inset-0 bg-black/75" @click="$wire.closeAnnouncements()"></div>

    <div class="absolute inset-0 flex items-center justify-center px-4 sm:px-6">
        <div
            class="w-full max-w-2xl"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform translate-y-3 opacity-0"
            x-transition:enter-end="transform translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="transform translate-y-0 opacity-100"
            x-transition:leave-end="transform translate-y-3 opacity-0"
        >
            <div class="glass rounded-3xl border border-white/10 bg-black/50 p-4 backdrop-blur sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-white/60">Avisos</p>
                        <p class="mt-1 text-xl font-semibold text-white/90">Anuncios recientes</p>
                        <p class="mt-1 text-sm text-white/40">{{ (int) ($unreadAnnouncements ?? 0) }} sin leer</p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if(($unreadAnnouncements ?? 0) > 0)
                            <x-portal.button variant="ghost" wire:click="markAllAnnouncementsAsRead">
                                Marcar leídas
                            </x-portal.button>
                        @endif

                        <x-portal.button variant="ghost" x-on:click="$wire.closeAnnouncements()">
                            Cerrar
                        </x-portal.button>
                    </div>
                </div>

                <div class="mt-4 max-h-[70vh] space-y-3 overflow-y-auto pr-1">
                    @forelse($announcements as $a)
                        <x-portal.card padding="p-4" class="tl-s2 tl-s2-hover tl-interactive" role="button" tabindex="0" wire:click="markAnnouncementAsRead({{ (int) ($a['id'] ?? 0) }})">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white/90 truncate">{{ $a['title'] ?? 'Aviso' }}</p>
                                    @if(!empty($a['content']))
                                        <div class="mt-1 text-xs text-white/50">{!! $a['content'] !!}</div>
                                    @endif
                                    <p class="mt-2 text-[11px] text-white/60">{{ $a['starts_at'] ?? '' }}</p>
                                </div>
                                @if(($a['read'] ?? false) === false)
                                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-emerald-400"></span>
                                @endif
                            </div>
                        </x-portal.card>
                    @empty
                        <x-portal.card padding="p-6">
                            <p class="text-white/60">No hay avisos nuevos.</p>
                            <p class="mt-2 text-sm text-white/40">Revisa más tarde para ver actualizaciones en tu portal.</p>
                        </x-portal.card>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
