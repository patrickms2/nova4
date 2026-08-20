<x-layout>
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">Resumen de Modelos</h1>

        @foreach ($models as $model)
        <div class="bg-white shadow rounded p-4 mb-6">
            <h2 class="text-xl font-semibold mb-2 text-blue-700">{{ $model['model'] }}</h2>
            <p class="text-sm text-gray-500 mb-2">
                Grupo: <strong>{{ $model['menu']['group'] ?? '-' }}</strong> |
                Orden: <strong>{{ $model['menu']['order'] ?? '-' }}</strong>
            </p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="font-bold text-gray-700">Campos</h3>
                    <ul class="list-disc pl-5 text-sm text-gray-800">
                        @foreach ($model['fields'] as $field)
                            <li>{{ $field['name'] }} <em class="text-gray-500">({{ $field['type'] }})</em></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-gray-700">Relaciones</h3>
                    <ul class="list-disc pl-5 text-sm text-gray-800">
                        @forelse ($model['relations'] as $rel)
                            <li>{{ $rel['type'] }} → {{ $rel['model'] }}</li>
                        @empty
                            <li class="text-gray-400">Sin relaciones</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</x-layout>
