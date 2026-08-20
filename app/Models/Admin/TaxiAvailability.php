<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TaxiAvailability extends BaseAdminModel
{
    protected $table = "admin_taxi_availability";

    protected string $adminLabelColumn = "date";

    protected $fillable = [
        "driver_id",
        "vehicle_id",
        "type",
        "status",
        "date",
        "start_time",
        "end_time",
        "notes",
    ];

    protected $casts = [
            "driver_id" => "integer",
            "vehicle_id" => "integer",
            "date" => "date",
        ];
    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class, "driver_id");
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TaxiVehicle::class, "vehicle_id");
    }
}
