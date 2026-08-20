<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Villa extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'location_id',
        'manager_id',

    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
    public function manager()
    {
        return $this->belongsTo(Admin::class, 'manager_id');
    }

    public function vehicles()
    {
        return $this->hasMany(RentalVehicle::class, 'office_id');
    }
    public function availabilities()
    {
        return $this->hasMany(VillaAvailability::class, 'villa_id');
    }
    public function rentals()
    {
        return $this->hasMany(Rental::class, 'villa_id');
    }

    public function rentable()
    {
        return $this->morphTo();
    }
}
