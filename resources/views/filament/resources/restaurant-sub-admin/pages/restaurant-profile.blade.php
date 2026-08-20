<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
   
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">
                🍽️ Restaurant Information
            </h2>

            <ul class="space-y-2 text-gray-900 dark:text-white">
                <li><strong class="text-gray-800 dark:text-gray-200">Name:</strong> {{ $restaurant->name }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Address:</strong> {{ $restaurant->location->city->name }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Discount:</strong> {{ $restaurant->discount?? "no discount" }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Opening Time:</strong> {{ $restaurant->openingTime }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Closing Time:</strong> {{ $restaurant->closingTime }}</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">
                👤 Admin Information
            </h2>

            <ul class="space-y-2 text-gray-900 dark:text-white">
                <li><strong class="text-gray-800 dark:text-gray-200">Name:</strong> {{ $restaurant->admin->name }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Email:</strong> {{ $restaurant->admin->email }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Role:</strong> {{ $restaurant->admin->role }}</li>
                <li><strong class="text-gray-800 dark:text-gray-200">Section:</strong> {{ $restaurant->admin->section }}</li>
            </ul>
        </div>
    </div>
</x-filament::page>
