<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\HasMany;
class Restaurant extends BaseAdminModel
{
    protected $table = "admin_restaurants";

    protected string $adminLabelColumn = "name";

    protected $fillable = [
        "name",
        "type",
        "status",
        "location",
        "cuisine",
        "capacity",
        "notes",
    ];

    protected $casts = [
            "capacity" => "integer",
        ];
    public function reservations(): HasMany
    {
        return $this->hasMany(RestaurantReservation::class, "restaurant_id");
    }
}
