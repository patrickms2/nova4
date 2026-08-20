<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'work_order_task_id',
        'user_id',
        'body',
        'edited_at',
        'edited_by',
        'active',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
            'active' => 'boolean',
            'is_read' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkOrderTask::class, 'work_order_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
