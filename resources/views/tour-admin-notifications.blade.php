<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse ($notifications as $notification)
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-200 dark:border-gray-700 mb-4">
        <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">
            🔔 Notification
        </h2>

        <p><strong>User Name:</strong> {{ $notification->data['user_name'] }}</p>
        <p><strong>User Email:</strong> {{ $notification->data['user_email'] }}</p>
        <p><strong>Tour Name:</strong> {{ $notification->data['tour_name'] }}</p>
        <p><strong>Destination:</strong> {{ $notification->data['destination'] }}</p>
        <p><strong>Start Date:</strong> {{ $notification->data['start_date'] }}</p>
        <p><strong>End Date:</strong> {{ $notification->data['end_date'] }}</p>

        <p class="mt-4 text-gray-900 dark:text-white">{{ $notification->data['message'] }}</p>
        <div class="flex space-x-4 mt-4">
            <button 
                wire:click="acceptRequest('{{ $notification->id }}')" 
                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Accept
            </button>
            <button 
                wire:click="rejectRequest('{{ $notification->id }}')" 
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                Reject
            </button>
        </div>
        <div class="text-gray-800 dark:text-gray-300 text-sm mt-2">
            {{ $notification->created_at->diffForHumans() }}
        </div>
    </div>
    @empty
    <p>No Notification Now .</p>
    @endforelse
</div>
