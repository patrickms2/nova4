<div>

    @if($showModal)
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
            <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                <h2 class="text-xl font-bold mb-4">{{ $mode === 'create' ? 'افزودن' : 'ویرایش' }}</h2>
                <form wire:submit.prevent="addTask">

                   @error('taskTitle')
                    <div class="flex items-center bg-red-500 text-white text-sm font-bold px-4 py-3" role="alert">
                        <p>{{ $message }}</p>
                    </div>
                    @enderror
                    <div class="mb-4">
                        <label for="taskTitle" class="block text-sm font-medium mb-1">عنوان</label>
                        <input type="text" wire:model="taskTitle" class="w-full p-2 border border-gray-300 rounded-lg" required>
                    </div>

                    @error('taskDescription')
                    <div class="flex items-center bg-red-500 text-white text-sm font-bold px-4 py-3" role="alert">
                        <p>{{ $message }}</p>
                    </div>
                    @enderror
                    <div class="mb-4">
                        <label for="taskDescription" class="block text-sm font-medium mb-1">توضیحات</label>
                        <textarea wire:model="taskDescription" rows="3" class="w-full p-2 border border-gray-300 rounded-lg"></textarea>
                    </div>

                    @error('taskDueDate')
                    <div class="flex items-center bg-red-500 text-white text-sm font-bold px-4 py-3" role="alert">
                        <p>{{ $message }}</p>
                    </div>
                    @enderror
                    <div class="mb-4">
                        <label for="taskDueDate" class="block text-sm font-medium mb-1">تاریخ انجام</label>
                        <input type="date" wire:model="taskDueDate" class="w-full p-2 border border-gray-300 rounded-lg">
                    </div>

                    @error('taskPriority')
                    <div class="flex items-center bg-red-500 text-white text-sm font-bold px-4 py-3" role="alert">
                        <p>{{ $message }}</p>
                    </div>
                    @enderror
                    <div class="mb-4">
                        <label for="taskPriority" class="block text-sm font-medium mb-1">اولویت</label>
                        <select wire:model="taskPriority" class="w-full p-2 border border-gray-300 rounded-lg">
                            <option value="low">پایین</option>
                            <option value="medium">متوسط</option>
                            <option value="high">بالا</option>
                        </select>
                    </div>
                    <div class="flex justify-between">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">بستن</button>
                        <button type="submit" class="px-4 py-2 text-white rounded-lg {{ $mode === 'create' ? 'bg-blue-500 hover:bg-blue-600' : 'bg-yellow-500 hover:bg-yellow-600' }}">{{ $mode === 'create' ? 'ساخت کار جدید' : 'اعمال ویرایش' }}</button>
                    </div>

                </form>
            </div>
        </div>
    @endif

</div>
