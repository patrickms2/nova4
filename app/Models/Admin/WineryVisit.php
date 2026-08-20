<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class WineryVisit extends BaseAdminModel
{
    protected $table = "admin_winery_visits";

    protected string $adminLabelColumn = "customer_name";

    protected $fillable = [
        "winery_id",
        "customer_name",
        "type",
        "status",
        "visit_at",
        "guests",
        "total_amount",
        "email",
        "notes",
    ];

    protected $casts = [
            "winery_id" => "integer",
            "visit_at" => "datetime",
            "guests" => "integer",
            "total_amount" => "decimal:2",
        ];
    public function winery(): BelongsTo
    {
        return $this->belongsTo(Winery::class, "winery_id");
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, "payable_id")->where("payable_type", "winery_visit");
    }
}
