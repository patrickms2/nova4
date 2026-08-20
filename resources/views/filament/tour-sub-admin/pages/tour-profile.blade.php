<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-primary-600 mb-4">🏨 Tour Information</h2>
            <ul class="space-y-2 text-gray-900">
                <li><strong>Name:</strong> {{ $tour->name }}</li>
                <li><strong>Location:</strong> {{ $tour->location->city->name ?? 'N/A' }}</li>
                <li><strong>Duration:</strong> {{ $tour->duration_days }} days / {{ $tour->duration_hours }} hrs</li>
                <li><strong>Base Price:</strong> ${{ $tour->base_price }}</li>
                <li><strong>Status:</strong> {{ $tour->is_active ? 'Active' : 'Inactive' }}</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">

            <h2 class="text-2xl font-bold text-primary-600 mb-4">👤 Admin Information</h2>
            <ul class="space-y-2 text-gray-900">
                <li><strong>Name:</strong> {{ $tour->admin->name }}</li>
                <li><strong>Email:</strong> {{ $tour->admin->email }}</li>
                <li><strong>Role:</strong> {{ $tour->admin->role }}</li>
                <li><strong>Section:</strong> {{ $tour->admin->section }}</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-primary-600 mb-4">📅 Schedules</h2>
            @foreach($tour->schedules as $schedule)
                <div class="border-b py-2">
                    <strong>{{ $schedule->start_date }} - {{ $schedule->end_date }}</strong><br>
                    Spots: {{ $schedule->available_spots }} -
                    Price: ${{ $schedule->price ?? $tour->base_price }}
                </div>
            @endforeach
        </div>

    </div>
</x-filament::page>
