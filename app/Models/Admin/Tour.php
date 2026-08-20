<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\HasMany;
class Tour extends BaseAdminModel
{
    protected $table = "admin_tours";

    protected string $adminLabelColumn = "name";

    protected $fillable = [
        "name",
        "type",
        "status",
        "location",
        "capacity",
        "price_person",
        "notes",
    ];

    protected $casts = [
            "capacity" => "integer",
            "price_person" => "decimal:2",
        ];
    public function reservations(): HasMany
    {
        return $this->hasMany(TourReservation::class, "tour_id");
    }
}
