<?php

declare(strict_types=1);

namespace App\Models\Taxi;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Issue extends Model
{
    /** @use HasFactory<\Database\Factories\IssueFactory> */
    use HasFactory;

    #[Scope]
    public function status(Builder $query, ?string $status): void
    {
        $query->when($status, function ($q, $status) {
            $q->where('status', $status);
        });
    }

    #[Scope]
    public function myIssue(Builder $query): void
    {
        $query->whereHas('users', function ($query) {
            $query->where('user_id', auth()->id());
        });
    }

    #[Scope]
    public function priority(Builder $query, ?string $priority): void
    {
        $query->when($priority, function ($q, $priority) {
            $q->where('priority', $priority);
        });
    }

    #[Scope]
    public function tag(Builder $query, ?string $tagId): void
    {
        $query->when($tagId, function ($q, $tagId) {
            $q->whereHas('tags', function ($tagQuery) use ($tagId) {
                $tagQuery->where('tags.id', $tagId);
            });
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'issue_tag');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'issue_user');
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => ProjectStatus::class,
            'priority' => ProjectPriority::class,
        ];
    }
}
