<?php

namespace App\Models;

use App\Enums\WorkSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkSession extends Model
{
    /** @use HasFactory<\Database\Factories\WorkSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'access_grant_id',
        'access_point_id',
        'user_id',
        'status',
        'started_at',
        'finish_requested_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => WorkSessionStatus::class,
        'started_at' => 'datetime',
        'finish_requested_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function accessGrant(): BelongsTo
    {
        return $this->belongsTo(AccessGrant::class);
    }

    public function accessPoint(): BelongsTo
    {
        return $this->belongsTo(AccessPoint::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workReport(): HasOne
    {
        return $this->hasOne(WorkReport::class);
    }

    public function domoticsEvents(): HasMany
    {
        return $this->hasMany(DomoticsEvent::class);
    }

    public function elapsedSeconds(): int
    {
        $end = $this->finished_at ?? now();

        return max(0, $this->started_at->diffInSeconds($end));
    }
}
