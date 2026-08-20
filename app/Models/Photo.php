<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'community_id',
        'work_order_id',
        'work_order_task_id',
        'incident_id',
        'path',
        'filename',
        'mime_type',
        'size',
        'taken_at',
        'uploaded_by',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'taken_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderTask(): BelongsTo
    {
        return $this->belongsTo(WorkOrderTask::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
