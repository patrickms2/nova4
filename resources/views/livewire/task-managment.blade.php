<div>

    <div class="flex space-x-4 overflow-x-auto" id="board">

        <!-- Columns will be appended here -->
        @php($statusTitle = array('به تعویق افتاده'=>'toDo', 'در حال انجام'=>'inProgress', 'کامل شده'=>'done'))

        @foreach(array_keys($statusTitle) as $key=>$Title)

            <div class="w-1/4 p-2">
                <div class="bg-gray-200 p-4 rounded-lg shadow-md" data-id="1724077561767">
                    <h2 class="text-xl font-bold mb-2" contenteditable="false">{{ $Title  }}</h2>

                    <div class="bg-white p-2 rounded-lg min-h-[300px]" id="column{{ $key }}" >
                        @foreach($tasks[$statusTitle[$Title]] as $task)

                            @switch($task['priority'])
                                @case('high')
                                    <div data-id="{{ $task->id }}" class="task-item p-2 mb-2 bg-red-100 border border-gray-300 rounded-lg draggable" draggable="true">
                                    @break

                                    @case('medium')
                                    <div data-id="{{ $task->id }}" class="task-item p-2 mb-2 bg-yellow-100 border border-gray-300 rounded-lg draggable" draggable="true">
                                    @break

                                    @case('low')
                                    <div data-id="{{ $task->id }}" class="task-item p-2 mb-2 bg-green-100 border border-gray-300 rounded-lg draggable" draggable="true">
                                    @break
                            @endswitch

                               <strong>{{ $task['title'] }}</strong>
                                <p>تاریخ انجام: {{ $task['created_at'] }}</p>
                                <p>اولویت: {{ $task['priority'] }}</p>
                                <div class="mt-2">
                                    <button wire:click="taskEdit({{ $task['id'] }})" class="mt-2 px-2 py-1 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">تغییر</button>
                                    <button wire:click="taskDelete({{ $task['id'] }})" class="mt-2 px-2 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600">حذف</button>
                                </div>

                            </div>
                        @endforeach
                    </div>

                    <button wire:click="addItem({{$key}})" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">افزودن کار</button>
                </div>
            </div>
        @endforeach

    </div>

</div>
