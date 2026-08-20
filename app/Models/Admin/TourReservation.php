<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TourReservation extends BaseAdminModel
{
    protected $table = "admin_tour_reservations";

    protected string $adminLabelColumn = "customer_name";

    protected $fillable = [
        "tour_id",
        "customer_name",
        "type",
        "status",
        "tour_date",
        "guests",
        "total_amount",
        "email",
        "notes",
    ];

    protected $casts = [
            "tour_id" => "integer",
            "tour_date" => "date",
            "guests" => "integer",
            "total_amount" => "decimal:2",
        ];
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, "tour_id");
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, "payable_id")->where("payable_type", "tour_reservation");
    }
}
