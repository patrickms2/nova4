<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\HasMany;
class Itinerary extends BaseAdminModel
{
    protected $table = "admin_itineraries";

    protected string $adminLabelColumn = "reference";

    protected $fillable = [
        "reference",
        "customer_name",
        "type",
        "status",
        "start_date",
        "end_date",
        "guests",
        "total_amount",
        "email",
        "notes",
    ];

    protected $casts = [
            "start_date" => "date",
            "end_date" => "date",
            "guests" => "integer",
            "total_amount" => "decimal:2",
        ];
    public function services(): HasMany
    {
        return $this->hasMany(ItineraryService::class, "itinerary_id");
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, "payable_id")->where("payable_type", "itinerary");
    }
}
