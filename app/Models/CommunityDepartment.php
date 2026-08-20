<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityDepartment extends Model
{
    use HasFactory;

    protected $fillable = ['community_id', 'name', 'slug', 'description', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'community_department_employee')->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(CommunityAppointment::class,);
    }
   public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class,'department_id');
    }
    public function tickets(): HasMany
    {
        return $this->hasMany(CommunityTicket::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(CommunityShift::class);
    }
}
