<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kirschbaum\Commentions\HasComments;
use Kirschbaum\Commentions\Contracts\Commentable;
class Incident extends Model implements Commentable
{
    use HasFactory, SoftDeletes, HasComments;

    protected $fillable = [
        'community_id',
        'community_plan_id',
 'property_id',
  'person_id',
        'work_order_id',
        'work_order_task_id',
        'work_category_id',
        'work_catalog_id',
        'user_id',
        'title',
        'description',
        'priority',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommunityProperty::class);
    }
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function communityPlan(): BelongsTo
    {
        return $this->belongsTo(CommunityPlan::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workOrderTask(): BelongsTo
    {
        return $this->belongsTo(WorkOrderTask::class);
    }

    public function workCategory(): BelongsTo
    {
        return $this->belongsTo(WorkCategory::class);
    }

    public function workCatalog(): BelongsTo
    {
        return $this->belongsTo(WorkCatalog::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
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
        return $this->hasMany(IncidentComment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
