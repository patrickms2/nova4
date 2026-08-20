<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
               
            <h2 class="text-2xl font-bold text-primary-600 mb-4">👤 Admin Information</h2>
            <ul class="space-y-2 text-gray-900">
                <li><strong>Name:</strong> {{ $package->agency->admin->name }}</li>
                <li><strong>Email:</strong> {{ $package->agency->admin->email }}</li>
                <li><strong>Role:</strong> {{ $package->agency->admin->role }}</li>
                <li><strong>Section:</strong> {{ $package->agency->admin->section }}</li>
            </ul>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-primary-600 mb-4">🏢 Travel Agency Info</h2>
            <ul class="space-y-2 text-gray-900">
                <li><strong>Name:</strong> {{ $package->agency->name }}</li>
                <li><strong>Email:</strong> {{ $package->agency->email }}</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-primary-600 mb-4">📦 Package Information</h2>
            <ul class="space-y-2 text-gray-900">
                <li><strong>Name:</strong> {{ $package->name }}</li>
                <li><strong>Type:</strong> {{ $package->type }}</li>
                <li><strong>Price:</strong> ${{ $package->price }}</li>
                <li><strong>Status:</strong> {{ $package->is_active ? 'Active' : 'Inactive' }}</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-primary-600 mb-4">📍 Destinations</h2>
            @forelse($package->destinations as $destination)
                <div class="border-b py-2">
                    <strong>{{ $destination->name }}</strong> — {{ $destination->location->name ?? '' }}
                </div>
            @empty
                <p>No destinations added.</p>
            @endforelse
        </div>

    </div>
</x-filament::page>
