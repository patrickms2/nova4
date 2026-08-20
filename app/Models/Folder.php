<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'status',
        'project_id',
        'parent_id',
        'created_by',
        'sort_order',
    ];

    protected $casts = [
        'color' => 'string',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('sort_order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getNotesCountAttribute(): int
    {
        return $this->notes()->count();
    }
}
