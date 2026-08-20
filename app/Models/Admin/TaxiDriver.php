<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\HasMany;
class TaxiDriver extends BaseAdminModel
{
    protected $table = "admin_taxi_drivers";

    protected string $adminLabelColumn = "name";

    protected $fillable = [
        "name",
        "type",
        "status",
        "email",
        "phone",
        "license_no",
        "notes",
    ];

    protected $casts = [];
    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(TaxiAvailability::class, "driver_id");
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(TaxiReservation::class, "driver_id");
    }
}
