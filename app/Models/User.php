<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Archilex\AdvancedTables\Concerns\HasViews;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Marcelodelgado\Announcements\Traits\HasAnnouncements;
use Kirschbaum\Commentions\Contracts\Commenter;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
class User extends Authenticatable implements FilamentUser, HasName, HasTenants, MustVerifyEmail, Commenter
{
    use HasAnnouncements;
    use HasApiTokens, HasFactory, Notifiable;
    use HasViews;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'name',
        'first_name',
        'last_name',
        'phone',
        'country_id',
        'user_type',
        'role',
        'active',
        'employee_id',
        'registration_date',
        'last_login_date',
        'status',
        'profile_image_url',
        'preferred_language',
        'is_email_verified',
        'is_phone_verified',
        'current_property_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'fullname',
        // 'location','status','latlng',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'registration_date' => 'datetime',
        'last_login_date' => 'datetime',
        'is_email_verified' => 'boolean',
        'is_phone_verified' => 'boolean',
        'active' => 'boolean',

    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getTenants(Panel $panel): Collection
    {
        $shared = $this->properties;
        $owned = $this->ownedProperties()->whereNotIn('properties.id', $shared->pluck('id'))->get();

        return $shared->merge($owned);
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->properties->contains($tenant)
            || $tenant instanceof Property && $tenant->owner_id === $this->id;
    }

    public function setFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;

    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;

    }

    /**
     * Get the country that owns the user.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function currentProperty(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'current_property_id');
    }

    public function startedWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'started_by');
    }

    public function finishedWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'finished_by');
    }

    public function createdIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'created_by');
    }

    public function person(): HasOne
    {
        return $this->hasOne(Person::class);
    }

    /**
     * Get the tours created by the user.
     */
    public function createdTours(): HasMany
    {
        return $this->hasMany(Tour::class, 'created_by', 'id');
    }

    /**
     * Get the bookings for the user.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id', 'id');
    }

    /**
     * Get the password for the user.
     * This method is required by Laravel's authentication system.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function name()
    {
        return 'email';
    }

    /**
     * Get the name of the user for Filament.
     */
    public function getFilamentName(): string
    {
        return trim($this->first_name.' '.$this->last_name) ?: ($this->getAttributeValue('name') ?? $this->email ?? 'Usuario');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
 public function availableAnnouncements(): Builder
    {
        $departmentId = $this->department_id;

        return Announcement::query()
            ->active()
            ->where('user_id', '!=', $this->id)
            ->where(function ($query) use ($departmentId) {
                $query->where('for_users', true);

                if ($departmentId) {
                    $query->orWhereHas('departments', fn ($query) => $query->where('departments.id', $departmentId));
                }

                if (in_array($this->role, ['owner', 'employee'], true)) {
                    $query->orWhere('for_clients', true);
                }
            })
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at');
    }

    public function unreadAnnouncements(): Builder
    {
        return $this->availableAnnouncements();
    }

    public function recentAnnouncements(): Builder
    {
        return $this->availableAnnouncements();
    }

    public function nextUnreadAnnouncement()
    {
        return $this->unreadAnnouncements()->first();
    }

    public function dismissedAnnouncements(): MorphToMany
    {
        return $this->morphToMany(Announcement::class, 'dismissable', 'dismissed_announcements')
            ->withPivot(['read_at'])
            ->withTimestamps();
    }
    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function generateCode()
    {

        $this->timestamps = false;
        $this->code = rand(1000, 9999);
        $this->expire_at = now()->addMinutes(10);
        $this->save();
    }
}
