<?php

namespace App\Jobs;

use App\Events\TaskUpdated;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class criticalJob implements ShouldQueue
{
    use Queueable, LivewireAlert;

    protected Task $task;

    /**
     * Create a new job instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        event(new TaskUpdated($this->task->title));
    } 
}
