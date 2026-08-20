<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class VillaReservation extends BaseAdminModel
{
    protected $table = "admin_villa_reservations";

    protected string $adminLabelColumn = "customer_name";

    protected $fillable = [
        "villa_id",
        "customer_name",
        "email",
        "type",
        "status",
        "check_in",
        "check_out",
        "guests",
        "total_amount",
        "notes",
    ];

    protected $casts = [
            "villa_id" => "integer",
            "check_in" => "date",
            "check_out" => "date",
            "guests" => "integer",
            "total_amount" => "decimal:2",
        ];
    public function villa(): BelongsTo
    {
        return $this->belongsTo(Villa::class, "villa_id");
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, "payable_id")->where("payable_type", "villa_reservation");
    }
}
