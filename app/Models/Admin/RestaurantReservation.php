<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class RestaurantReservation extends BaseAdminModel
{
    protected $table = "admin_restaurant_reservations";

    protected string $adminLabelColumn = "customer_name";

    protected $fillable = [
        "restaurant_id",
        "customer_name",
        "type",
        "status",
        "reserved_at",
        "guests",
        "email",
        "notes",
    ];

    protected $casts = [
            "restaurant_id" => "integer",
            "reserved_at" => "datetime",
            "guests" => "integer",
        ];
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, "restaurant_id");
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, "payable_id")->where("payable_type", "restaurant_reservation");
    }
}
