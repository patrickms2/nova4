<?php

namespace App\Models\Admin;

class ConciergeBooking extends BaseAdminModel
{
    protected $table = "concierge_bookings";

    protected string $adminLabelColumn = "reference";

    protected $fillable = [
        "reference",
        "customer_name",
        "customer_email",
        "plan_type",
        "travel_date",
        "guests",
        "pickup_address",
        "ai_prompt",
        "include_taxi",
        "include_products",
        "products_note",
        "itinerary_json",
        "total_amount",
        "status",
        "payment_status",
    ];

    protected $casts = [
        "travel_date" => "date",
        "guests" => "integer",
        "include_taxi" => "boolean",
        "include_products" => "boolean",
        "total_amount" => "decimal:2",
    ];
}
