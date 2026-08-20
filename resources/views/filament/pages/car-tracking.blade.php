<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-4">
        <x-filament::section><div class="text-sm text-gray-500">Dispositivos visibles</div><div class="text-2xl font-semibold">{{ $visibleDevicesCount }}</div></x-filament::section>
        <x-filament::section><div class="text-sm text-gray-500">Posiciones visibles</div><div class="text-2xl font-semibold">{{ $visiblePositionsCount }}</div></x-filament::section>
        <x-filament::section><div class="text-sm text-gray-500">Dispositivos remotos</div><div class="text-2xl font-semibold">{{ $remoteDevicesCount }}</div></x-filament::section>
        <x-filament::section><div class="text-sm text-gray-500">Conexión</div><div class="text-lg font-semibold">{{ $traccarAuthenticated ? 'Conectado' : 'Sin conexión' }}</div></x-filament::section>
    </div>
</x-filament-panels::page>
