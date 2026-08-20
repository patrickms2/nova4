<?php

namespace App\Livewire;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Events\TaskUpdated;
use App\Jobs\criticalJob;
use App\Models\Task;
use Livewire\Attributes\Rule;
use Livewire\Component;

class AddTaskModal extends Component
{
    public $mode = 'create';
    protected $listeners = ['openModal' => 'handleOpenModal'];
    public $showModal = false;
    public $status = 'toDo';
    public $itemId;

    #[Rule('required|min:5|max:255')]
    public $taskTitle;

    #[Rule('nullable')]
    public $taskDescription;

    #[Rule('nullable|date')]
    public $taskDueDate;

    #[Rule('required|string')]
    public $taskPriority = 'medium';


    public function handleOpenModal($arr)
    {
        $this->showModal = true;
        $this->mode = $arr[0];


        if ($arr[0] === 'create'){
            switch ($arr[1]){
                case 0:
                    $this->status = TaskStatus::TODO;
                    break;
                case 1:
                    $this->status = TaskStatus::INPROGRESS;
                    break;
                case 2:
                    $this->status = TaskStatus::DONE;
                    break;
            }
        } else {
            $this->itemId = $arr[1]['id'];
            $this->taskTitle = $arr[1]['title'];
            $this->taskDescription = $arr[1]['desc'] ?? null;
            $this->taskDueDate = $arr[1]['date'] ?? null;
            $this->taskPriority = $arr[1]['priority'];
        }
    }

    public function closeModal()
    {
        $this->reset();
    }

    public function addTask()
    {
        $validated = $this->validate();

        if ($this->mode === 'create') {
            // آیتم جدید را ایجاد کن
            $task = Task::create([
                'title' => $validated['taskTitle'],
                'desc' => $validated['taskDescription'],
                'priority' => $validated['taskPriority'],
                'created_at' => $validated['taskDueDate'],
                'status' => $this->status,
            ]);
 
        } elseif ($this->mode === 'edit') {
            // آیتم موجود را آپدیت کن
            $task = Task::findOrFail($this->itemId);

            $task->update([
            'title' => $validated['taskTitle'],
            'desc' => $validated['taskDescription'],
            'priority' => $validated['taskPriority'],
            'created_at' => $validated['taskDueDate'],
            ]);
        }

        $queue = $task->priority === TaskPriority::HIGH ? "critical" : "default";
        dispatch((new criticalJob($task)))->onQueue($queue);


        $this->dispatch('taskCreated');
        $this->closeModal();
        $this->dispatch('taskAdded'); // Optional: Emit an event to update task lists
    }

    public function render()
    {
        return view('livewire.add-task-modal');
    }

}


