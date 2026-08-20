<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TarfinLabs\LaravelSpatial\Casts\LocationCast;

class Driver extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'drivers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'user_id',
        'taxi_service_id',
        'license_number',
        'experience_years',
        'current_location',
        'rating',
        'rating_count',
        'availability_status',
        'last_seen_at',
        'location_updated_at',
        'is_active',
        'shift_start',
        'shift_end',
        'rating_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'experience_years' => 'integer',
        'rating' => 'decimal:2',
        'current_location' => LocationCast::class,
        'last_seen_at' => 'datetime',
        'location_updated_at' => 'datetime',
        'is_active' => 'boolean',
        'shift_start' => 'datetime:H:i',
        'shift_end' => 'datetime:H:i',
        'rating_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    public function userName()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the taxi service that the driver belongs to.
     */
    public function taxiService()
    {
        return $this->belongsTo(TaxiService::class, 'taxi_service_id', 'id');
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'driver_vehicle_assignments')
            ->withPivot(['assigned_at', 'unassigned_at'])
            ->withTimestamps();
    }

    public function activeVehicle()
    {
        return $this->vehicles()
            ->whereNull('driver_vehicle_assignments.unassigned_at')
            ->latest('driver_vehicle_assignments.assigned_at');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get the taxi bookings for the driver.
     */
    public function taxiBookings()
    {
        return $this->hasMany(TaxiBooking::class, 'driver_id', 'id');
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function scopeAvailable($query)
    {
        return $query->where('availability_status', 'available');
    }

    public function scopeRecentlyActive($query)
    {
        return $query->whereNotNull('location_updated_at')
            ->where('last_seen_at', '>=', now()->subMinutes(5));
    }
}
