<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityPlanItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'community_plan_id',
        'work_catalog_id',
        'title',
        'instructions',
        'requirements',
        'sort',
        'active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommunityPlan::class,'community_plan_id');
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(WorkCatalog::class, 'work_catalog_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(CommunityPlanDay::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkOrderTask::class);
    }

    public function candidateEmployees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'community_plan_item_employee')->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
