<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kirschbaum\Commentions\HasComments;
use Kirschbaum\Commentions\Contracts\Commentable;

class WorkOrderTask extends Model implements Commentable
{
    use HasFactory, SoftDeletes, HasComments;

    protected $fillable = [
        'work_order_id',
        'community_id',
        'community_plan_id',       
        'community_plan_item_id',
        'user_id',
        'source_type',
        'title',
        'instructions',
        'requirements',
        'priority',
        'status',
        'completed_by',
        'completed_at',
        'result',
        'requester_name',
        'requester_phone',
        'reference',
        'sort',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'sort' => 'integer',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
        public function plan(): BelongsTo
    {
        return $this->belongsTo(CommunityPlan::class,'community_plan_id');
    }
        public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'user_id');
    }
        public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function planItem(): BelongsTo
    {
        return $this->belongsTo(CommunityPlanItem::class);
    }
        public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function comments2(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
