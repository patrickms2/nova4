<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'folder_id',
        'project_id',
        'created_by',
        'tags',
        'is_pinned',
        'pinned_at',
        'clickup_doc_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeRecent($query)
    {
        return $query->latest();
    }

    public function getExcerptAttribute(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->content), 150);
    }

    public function pin(): void
    {
        $this->update([
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);
    }

    public function unpin(): void
    {
        $this->update([
            'is_pinned' => false,
            'pinned_at' => null,
        ]);
    }
}
