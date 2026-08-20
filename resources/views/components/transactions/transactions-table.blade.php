@props([
    'transactions',
    'sortBy',
    'sortDir',
    'isFiltering' => false,
])

<div class="overflow-x-auto">
    <x-ui.table>
        <x-ui.table-header>
            <x-ui.table-head class="w-8 pl-4">
                <div class="flex items-center justify-center">
                    <input type="checkbox" class="rounded border-input size-4 cursor-pointer"
                        wire:model.live="selectAll"
                        @click="$wire.toggleSelectAll()">
                </div>
            </x-ui.table-head>
            <x-ui.table-head class="cursor-pointer" wire:click="sort('date')">
                Date
            </x-ui.table-head>

            <x-ui.table-head>Description</x-ui.table-head>

            <x-ui.table-head class="cursor-pointer" wire:click="sort('category')">
                Category
            </x-ui.table-head>

            <x-ui.table-head>Type</x-ui.table-head>

            <x-ui.table-head class="cursor-pointer text-end" wire:click="sort('amount')">
                Amount
            </x-ui.table-head>

            <x-ui.table-head>Attachment</x-ui.table-head>
            <x-ui.table-head>Action</x-ui.table-head>
        </x-ui.table-header>

        <x-ui.table-body>
            @forelse($transactions as $transaction)
                <x-ui.table-row :key="$transaction->id">
                    <x-ui.table-cell class="text-xs text-gray-400 whitespace-nowrap">
                        {{ $transaction->date?->format('d M Y') ?? '—' }}
                    </x-ui.table-cell>

                    <x-ui.table-cell class="font-medium">
                        {{ $transaction->description }}
                    </x-ui.table-cell>

                    <x-ui.table-cell>
                        {{ $transaction->category?->name ?? '—' }}
                    </x-ui.table-cell>

                    <x-ui.table-cell>
                        @if($transaction->type === 'income')
                            <x-ui.badge tone="success" size="sm">Income</x-ui.badge>
                        @else
                            <x-ui.badge tone="danger" size="sm">Expense</x-ui.badge>
                        @endif
                    </x-ui.table-cell>

                    <x-ui.table-cell
                        class="text-end font-medium {{ $transaction->type === 'income' ? 'text-green-500' : 'text-red-500' }}">
                        {{ $transaction->type === 'income' ? '+' : '-' }}
                        RM {{ number_format($transaction->amount, 2) }}
                    </x-ui.table-cell>

                    <x-ui.table-cell>
                        @if($transaction->latestAttachment)
                            <a
                                href="{{ $transaction->latestAttachment->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm text-blue-500 hover:underline"
                            >
                                View
                            </a>
                        @else
                            <span class="text-sm text-gray-400">—</span>
                        @endif
                    </x-ui.table-cell>

                    <x-ui.table-cell>
                        <div class="flex items-center gap-1">
                            <x-ui.button
                                wire:click="openEdit({{ $transaction->id }})"
                                variant="ghost"
                                size="sm">
                                <x-slot name="before">
                                    <x-lucide-pencil class="size-4" />
                                </x-slot>
                            </x-ui.button>

                            <x-ui.button
                                wire:click="confirmDelete({{ $transaction->id }}, @js($transaction->description))"
                                variant="ghost"
                                size="sm"
                                class="text-red-400 hover:text-red-600">
                                <x-slot name="before">
                                    <x-lucide-trash class="size-4" />
                                </x-slot>
                            </x-ui.button>
                        </div>
                    </x-ui.table-cell>
                </x-ui.table-row>
            @empty
                <x-ui.table-row>
                    <x-ui.table-cell colspan="7">
                        <div class="flex flex-col items-center justify-center py-16 text-center">
                            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-zinc-700 flex items-center justify-center mb-4">
                                <x-lucide-wallet class="w-8 h-8 text-gray-400 dark:text-zinc-500" />
                            </div>

                            <x-ui.heading size="sm" class="text-gray-500 dark:text-zinc-400 mb-1">
                                No transactions yet
                            </x-ui.heading>

                            <x-ui.subheading class="text-gray-400 dark:text-zinc-500 mb-6 max-w-xs">
                                Start tracking your finances by adding your first transaction.
                            </x-ui.subheading>

                            @if($isFiltering)
                                <x-ui.button
                                    wire:click="$set('search', ''); $set('filterType', ''); $set('filterCategory', '')"
                                    variant="ghost"
                                    size="sm">
                                    <x-slot name="before">
                                        <x-lucide-x class="size-4" />
                                    </x-slot>
                                    Clear Filters
                                </x-ui.button>
                            @endif

                            <x-ui.button wire:click="openCreate" variant="primary" size="sm">
                                <x-slot name="before">
                                    <x-lucide-plus class="size-4" />
                                </x-slot>
                                Add First Transaction
                            </x-ui.button>
                        </div>
                    </x-ui.table-cell>
                </x-ui.table-row>
            @endforelse
        </x-ui.table-body>
    </x-ui.table>
</div>