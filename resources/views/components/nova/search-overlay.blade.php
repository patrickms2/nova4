@props([
    'items' => [],
    'placeholder' => 'Search everything…',
])

@php
    $searchItems = collect($items)
        ->map(fn (array $item): array => [
            'label' => $item['label'],
            'group' => $item['group'] ?? 'Results',
            'url' => $item['url'] ?? null,
            'meta' => $item['meta'] ?? null,
        ])
        ->values()
        ->all();
@endphp

<div
    x-data="{
        open: false,
        query: '',
        active: 0,
        items: @js($searchItems),
        get results() {
            const query = this.query.trim().toLowerCase();

            if (query === '') {
                return this.items;
            }

            return this.items.filter((item) => (item.label + ' ' + item.group + ' ' + (item.meta ?? '')).toLowerCase().includes(query));
        },
        show() {
            this.open = true;
            this.query = '';
            this.active = 0;
            this.$nextTick(() => this.$refs.input?.focus());
        },
        move(step) {
            const total = this.results.length;

            if (total === 0) {
                return;
            }

            this.active = (this.active + step + total) % total;
        },
        select() {
            const item = this.results[this.active];

            if (item?.url) {
                window.location.href = item.url;
            }
        },
    }"
    x-on:keydown.window.meta.k.prevent="show()"
    x-on:keydown.window.ctrl.k.prevent="show()"
    x-on:nova-search.window="show()"
    x-on:keydown.escape.window="open = false"
    {{ $attributes }}
>
    <template x-if="open">
        <div
            class="fixed inset-0 z-50 flex items-start justify-center bg-black/70 px-6 py-24"
            x-on:click.self="open = false"
            role="dialog"
            aria-modal="true"
            aria-label="Nova universal search"
        >
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-900 shadow-sm">
                <div class="border-b border-neutral-800 px-4">
                    <input
                        x-ref="input"
                        x-model="query"
                        x-on:keydown.arrow-down.prevent="move(1)"
                        x-on:keydown.arrow-up.prevent="move(-1)"
                        x-on:keydown.enter.prevent="select()"
                        type="text"
                        placeholder="{{ $placeholder }}"
                        autocomplete="off"
                        class="w-full border-0 bg-transparent px-0 py-4 text-[15px] text-white placeholder:text-neutral-500 focus:ring-0"
                    >
                </div>

                <div class="max-h-96 overflow-y-auto p-2">
                    <template x-for="(item, index) in results" :key="item.group + '-' + item.label">
                        <a
                            :href="item.url ?? '#'"
                            x-on:mouseenter="active = index"
                            :class="active === index ? 'bg-neutral-800 text-white' : 'text-neutral-400'"
                            class="flex items-center justify-between gap-4 rounded-2xl px-3 py-3 text-sm transition-colors"
                        >
                            <span class="min-w-0 truncate" x-text="item.label"></span>
                            <span class="shrink-0 text-xs uppercase tracking-widest text-neutral-500" x-text="item.meta ?? item.group"></span>
                        </a>
                    </template>

                    <template x-if="results.length === 0">
                        <p class="px-3 py-6 text-center text-sm text-neutral-500">
                            Nothing found.
                        </p>
                    </template>
                </div>

                <div class="flex items-center justify-between border-t border-neutral-800 px-4 py-3 text-xs text-neutral-500">
                    <span>↑ ↓ to navigate · Enter to open</span>
                    <span>Esc to close</span>
                </div>
            </div>
        </div>
    </template>
</div>
