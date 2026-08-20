<?php

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'first_name', 'last_name', 'display_name', 'email', 'phone', 'document_type', 'document_number', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function scopeOwner(Builder $query): Builder
    {
        return $query->where('role', 'owner');
    }
    public function scopeEmployee(Builder $query): Builder
    {
        return $query->where('role', 'employee');
    }
    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }
    public function roles(): HasMany
    {
        return $this->hasMany(PersonRole::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(CommunityProperty::class,'community_property_person')->withPivot(['role', 'metadata'])->withTimestamps();
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(AccessGrant::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(RentalReservation::class);
    }

    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class,'community_person')->withPivot(['role', 'is_active', 'metadata'])->withTimestamps();
    }

    public function communityDocuments(): HasMany
    {
        return $this->hasMany(CommunityOwnerDocument::class);
    }

    public function communityAppointments(): HasMany
    {
        return $this->hasMany(CommunityAppointment::class);
    }

    public function communityTickets(): HasMany
    {
        return $this->hasMany(CommunityTicket::class);
    }

    public function communityFees(): HasMany
    {
        return $this->hasMany(CommunityFee::class, 'person_id');
    }
}
