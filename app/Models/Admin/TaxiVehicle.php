<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\HasMany;
class TaxiVehicle extends BaseAdminModel
{
    protected $table = "admin_taxi_fleet";

    protected string $adminLabelColumn = "name";

    protected $fillable = [
        "name",
        "type",
        "status",
        "plate",
        "seats",
        "price_base",
        "notes",
    ];

    protected $casts = [
            "seats" => "integer",
            "price_base" => "decimal:2",
        ];
    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(TaxiAvailability::class, "vehicle_id");
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(TaxiReservation::class, "vehicle_id");
    }
}
