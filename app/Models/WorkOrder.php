<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kirschbaum\Commentions\Contracts\Commentable;
use Kirschbaum\Commentions\HasComments;

class WorkOrder extends Model implements Commentable
{
    use HasComments, HasFactory, SoftDeletes;

    protected $fillable = [
        'community_id',
        'community_plan_id',
        'user_id',
        'code',
        'work_date',
        'status',
        'source_type',
        'started_by',
        'started_at',
        'finished_by',
        'finished_at',
        'requester_name',
        'requester_phone',
        'reference',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommunityPlan::class, 'community_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'user_id');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function finisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finished_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkOrderTask::class);
    }

    public function orderComments(): HasMany
    {
        return $this->hasMany(WorkOrderComment::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function communityShifts(): HasMany
    {
        return $this->hasMany(CommunityShift::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
