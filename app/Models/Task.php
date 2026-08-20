<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *     schema="Task",
 *     type="object",
 *     title="Task",
 *     description="A task in the task management system",
 *     required={"title", "priority", "status"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Task ID"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="Title of the task"
 *     ),
 *     @OA\Property(
 *         property="desc",
 *         type="string",
 *         description="Description of the task"
 *     ),
 *     @OA\Property(
 *         property="date",
 *         type="string",
 *         format="date",
 *         description="Date of the task"
 *     ),
 *     @OA\Property(
 *         property="priority",
 *         type="string",
 *         description="Priority level of the task"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"toDo", "inProgress", "done"},
 *         description="Current status of the task"
 *     )
 * )
 */

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'type',
        'due_date',
        'assigned_to',
        'project_id',
        'task_category_id',
        'sort_order',
        'is_completed',
        'completed_at',
        'clickup_task_id',
        'created_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function taskCategory(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function markAsPending(): void
    {
        $this->update([
            'status' => 'pending',
            'is_completed' => false,
            'completed_at' => null,
        ]);
    }
}
