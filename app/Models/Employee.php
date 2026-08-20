<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'employee_code',
        'position',
        'start_date',
        'active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkOrderTask::class, 'user_id');
    }

    public function communityDepartments(): BelongsToMany
    {
        return $this->belongsToMany(CommunityDepartment::class, 'community_department_employee')->withTimestamps();
    }

    public function workCategories(): BelongsToMany
    {
        return $this->belongsToMany(WorkCategory::class, 'employee_work_category')->withTimestamps();
    }

    public function candidatePlanItems(): BelongsToMany
    {
        return $this->belongsToMany(CommunityPlanItem::class, 'community_plan_item_employee')->withTimestamps();
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'user_id');
    }

    public function communityShifts(): HasMany
    {
        return $this->hasMany(CommunityShift::class);
    }

    public function communityAttendances(): HasMany
    {
        return $this->hasMany(CommunityAttendance::class);
    }

    public function communityDocuments(): HasMany
    {
        return $this->hasMany(CommunityEmployeeDocument::class);
    }
}
