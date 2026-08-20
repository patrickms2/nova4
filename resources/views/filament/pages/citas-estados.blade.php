<x-filament-panels::page>
    <div class="grid gap-3">
        @foreach($this->getStates() as $state)
            <div class="rounded-lg border p-4">
                <div class="font-semibold">{{ $state['label'] }}</div>
                <div class="text-sm text-gray-500">{{ $state['value'] }}</div>
                <div class="text-sm">{{ $state['description'] }}</div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
