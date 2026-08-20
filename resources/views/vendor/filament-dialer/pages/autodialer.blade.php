<x-filament-panels::page>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Autodialer
        </h2>
    </x-slot>

    <div>
        <div class="grid grid-cols-2 gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    Dialer
                </x-slot>

                @livewire('filament-dialer.phone-dialer-sidebar')
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Current Call
                </x-slot>

                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-phone" class="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>No active call</p>
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
        <x-slot name="heading">
            Phone Queue
        </x-slot>

        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Address</th>
                        <th class="px-6 py-3">Phone</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if (auth()->check() && auth()->user()->phone_queues->count() > 0)
                        @foreach (auth()->user()->phone_queues as $queueItem)
                            <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4">{{ $queueItem->name }}</td>
                                <td class="px-6 py-4">{{ $queueItem->address }}</td>
                                <td class="px-6 py-4">{{ $queueItem->phone }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $queueItem->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($queueItem->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300') }}">
                                        {{ ucfirst($queueItem->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-700 dark:hover:bg-primary-800">
                                        <x-filament::icon icon="heroicon-o-phone" class="h-4 w-4" />
                                        Call
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                            <td colspan="5" class="px-6 py-8 text-center">
                                <x-filament::icon icon="heroicon-o-inbox" class="mx-auto mb-2 h-12 w-12 opacity-50" />
                                <p class="text-gray-500 dark:text-gray-400">No phone queue items</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-filament::section>
    </div>
</x-filament-panels::page>
