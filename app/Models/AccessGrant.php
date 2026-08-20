<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AccessGrantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccessGrant extends Model
{
    /** @use HasFactory<AccessGrantFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'rental_property_id',
        'user_id',
        'person_id',
        'booking_id',
        'name',
        'role',
        'pin',
        'valid_from',
        'valid_until',
        'allowed_weekdays',
        'allowed_time_from',
        'allowed_time_until',
        'is_active',
        'report_required',
        'voice_required',
        'photo_required',
        'minimum_photos',
        'revoked_at',
        'created_by',
        'source_type',
        'source_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'allowed_weekdays' => 'array',
        'is_active' => 'boolean',
        'report_required' => 'boolean',
        'voice_required' => 'boolean',
        'photo_required' => 'boolean',
        'minimum_photos' => 'integer',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommunityProperty::class);
    }

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function credentials(): BelongsToMany
    {
        return $this->belongsToMany(Credential::class)->withTimestamps();
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('revoked_at')->where(fn ($query) => $query->whereNull('valid_from')->orWhere('valid_from', '<=', now()))->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', now()));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accessPoints(): BelongsToMany
    {
        return $this->belongsToMany(AccessPoint::class, 'access_grant_access_point')
            ->withTimestamps();
    }

    public function domoticsEvents(): HasMany
    {
        return $this->hasMany(DomoticsEvent::class);
    }

    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }

    public function isValidForAccessPoint(AccessPoint $accessPoint): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->valid_from !== null && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until !== null && $this->valid_until->isPast()) {
            return false;
        }

        return $this->accessPoints->contains($accessPoint);
    }

    /**
     * Server-authoritative check: is this grant authorized for the given
     * access point at the given moment (weekday + time window included)?
     */
    public function isAuthorizedAt(AccessPoint $accessPoint, CarbonInterface $at): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if (! $this->isValidForAccessPoint($accessPoint)) {
            return false;
        }

        if (! $this->isWeekdayAllowed($at)) {
            return false;
        }

        if (! $this->isTimeAllowed($at)) {
            return false;
        }

        return true;
    }

    public function isWeekdayAllowed(CarbonInterface $at): bool
    {
        if ($this->allowed_weekdays === null || $this->allowed_weekdays === []) {
            return true;
        }

        return in_array($at->dayOfWeekIso, array_map('intval', $this->allowed_weekdays), true);
    }

    public function isTimeAllowed(CarbonInterface $at): bool
    {
        if ($this->allowed_time_from === null && $this->allowed_time_until === null) {
            return true;
        }

        $time = $at->format('H:i:s');
        $from = $this->allowed_time_from !== null ? substr((string) $this->allowed_time_from, 0, 8) : '00:00:00';
        $until = $this->allowed_time_until !== null ? substr((string) $this->allowed_time_until, 0, 8) : '23:59:59';

        return $time >= $from && $time <= $until;
    }
}
