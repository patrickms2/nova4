<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TaxiReservation extends BaseAdminModel
{
    protected $table = "admin_taxi_reservations";

    protected string $adminLabelColumn = "customer_name";

    protected $fillable = [
        "customer_name",
        "type",
        "status",
        "pickup_at",
        "pickup_address",
        "dropoff_address",
        "guests",
        "total_amount",
        "driver_id",
        "vehicle_id",
        "email",
        "notes",
    ];

    protected $casts = [
            "pickup_at" => "datetime",
            "guests" => "integer",
            "total_amount" => "decimal:2",
            "driver_id" => "integer",
            "vehicle_id" => "integer",
        ];
    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class, "driver_id");
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TaxiVehicle::class, "vehicle_id");
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, "payable_id")->where("payable_type", "taxi_reservation");
    }
}
