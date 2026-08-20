<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ItineraryService extends BaseAdminModel
{
    protected $table = "admin_itinerary_services";

    protected string $adminLabelColumn = "label";

    protected $fillable = [
        "itinerary_id",
        "label",
        "type",
        "status",
        "service_type",
        "service_id",
        "service_date",
        "total_amount",
        "notes",
    ];

    protected $casts = [
            "itinerary_id" => "integer",
            "service_id" => "integer",
            "service_date" => "datetime",
            "total_amount" => "decimal:2",
        ];
    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class, "itinerary_id");
    }
}
