<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
   
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">
                🏨 Hotel Information
            </h2>

            <ul class="space-y-2 text-gray-900 dark:text-white">
                <li><strong class="text-gray-800 dark:text-gray-200">Name:</strong> {{ $hotel->name }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Address:</strong> {{ $hotel->location->city->name }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Discount:</strong> {{ $hotel->discount??"No Discount" }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Opening Time:</strong> {{ $hotel->checkInTime }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Closing Time:</strong> {{ $hotel->checkOutTime }}</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">
                👤 Admin Information
            </h2>

            <ul class="space-y-2 text-gray-900 dark:text-white">
                <li><strong class="text-gray-800 dark:text-gray-200">Name:</strong> {{ $hotel->admin->name }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Email:</strong> {{ $hotel->admin->email }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Role:</strong> {{ $hotel->admin->role }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Section:</strong> {{ $hotel->admin->section }}</li>
            </ul>
        </div>
    </div>
</x-filament::page>
