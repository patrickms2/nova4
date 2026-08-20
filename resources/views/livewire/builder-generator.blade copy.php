<div class="bg-white p-4 rounded shadow mb-6">
    <h2 class="text-lg font-semibold text-indigo-600 mb-2">📥 Importar archivo JSON de modelos</h2>
    <form wire:submit.prevent="importSchemas">
        <input type="file" wire:model="importFile" accept=".json" class="mb-2">
        @error('importFile') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        <br>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-1 rounded">Importar modelos</button>
    </form>
    @if(session()->has('success'))
        <div class="text-green-600 mt-2">{{ session('success') }}</div>
    @endif
</div>
