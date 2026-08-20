<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'status',
        'phase',
        'parent_id',
        'project_category_id',
        'start_date',
        'end_date',
        'is_public',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'color' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_public' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Project::class, 'parent_id')->orderBy('sort_order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByPhase($query, $phase)
    {
        return $query->where('phase', $phase);
    }

    public function getTasksCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    public function getCompletedTasksCountAttribute(): int
    {
        return $this->tasks()->where('status', 'completed')->count();
    }

    public function getPendingTasksCountAttribute(): int
    {
        return $this->tasks()->where('status', 'pending')->count();
    }

    public function getProgressAttribute(): float
    {
        $total = $this->tasks_count;
        if ($total === 0) return 0;
        return ($this->completed_tasks_count / $total) * 100;
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->start_date || !$this->end_date) return null;
        return $this->start_date->diff($this->end_date)->days . ' días';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast() && $this->phase !== 'completed';
    }
}
