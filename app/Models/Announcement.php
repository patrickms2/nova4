<?php

namespace App\Models;

use App\Models\CommunityDepartment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\AnnouncementType;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'type',
        'user_id',
        'for_users',
        'for_clients',
        'is_dismissible',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'type' => AnnouncementType::class,
        'is_dismissible' => 'boolean',
        'is_active' => 'boolean',
        'for_users' => 'boolean',
        'for_clients' => 'boolean',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($announcement) {
            $announcement->user_id = auth()->user()->id;
        });

    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class, 
            'user_id'
        );

    }

        public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class, 
            'announcement_user'
        )->withPivot('dismissed_at')
                    ->withTimestamps();

    }
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(CommunityDepartment::class, 'announcement_department','department_id','announcement_id')
->withPivot('dismissed_at');
    }

       public function clients(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class, 
            'announcement_user'
        )->withPivot('dismissed_at');
    }
     public function dimissedByUser(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class, 
            'announcement_user'
        )->withPivot('dismissed_at')
                    ->withTimestamps();

    }

    public function dimissedByDepartments(): BelongsToMany
    {
        return $this->belongsToMany(CommunityDepartment::class, 'announcement_department','department_id','announcement_id')
            ->withPivot('dismissed_at')
            ->withTimestamps();
    }

    public function scopeOrderedForDisplay(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE type WHEN 'danger' THEN 1 WHEN 'warning' THEN 2 WHEN 'info' THEN 3 WHEN 'success' THEN 4 ELSE 5 END")
            ->orderByDesc('created_at');
    }
    public function scopeActive($query)
    {
        $now = now();

        return $query
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            });
    }
    public function isExpiredByDate(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
    public function scopeVisibleForDashboard(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
    public function scopeForUsers($query)
    {
        return $query->where('for_users', true);
    }

    public function scopeForClients($query)
    {
        return $query->where('for_clients', true);
    }
}
