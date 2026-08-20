<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'created_by',
        'sort_order',
    ];

    protected $casts = [
        'color' => 'string',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'project_category_id')->orderBy('sort_order');
    }

    public function getProjectsCountAttribute(): int
    {
        return $this->projects()->count();
    }

    public function scopeActive($query)
    {
        return $query->whereHas('projects', fn ($q) => $q->where('status', 'active'));
    }
}
