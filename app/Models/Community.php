<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Community extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'address',
        'contact_name',
        'contact_phone',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(CommunityPlan::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function workOrderTasks(): HasManyThrough
    {
        return $this->hasManyThrough(WorkOrderTask::class, WorkOrder::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)->withPivot(['role', 'is_active', 'metadata'])->withTimestamps();
    }
    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)->withPivot(['role', 'is_active', 'metadata'])->withTimestamps();
    }
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'community_employees')->withPivot(['role', 'is_active', 'metadata'])->withTimestamps();
    }    
    /*public function comunityProperties(): BelongsToMany
    {
        return $this->belongsToMany(CommunityProperty::class, 'community_properties', 'community_id', 'community_property_id')->withPivot(['role', 'metadata'])->withTimestamps();
    }*/
public function properties(): HasMany
    {
        return $this->hasMany(CommunityProperty::class, 'community_id', 'id');
    }
    public function ownerDocuments(): HasMany
    {
        return $this->hasMany(CommunityOwnerDocument::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(CommunityAppointment::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(CommunityTicket::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(CommunityFee::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(CommunityDepartment::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(CommunityShift::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CommunityAttendance::class);
    }

    public function attendanceSessions(): BelongsToMany
    {
        return $this->belongsToMany(CommunityAttendance::class, 'community_attendance_community')->withTimestamps();
    }
}
