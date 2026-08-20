<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
class VillaRate extends BaseAdminModel
{
    protected $table = "admin_villa_rates";

    protected string $adminLabelColumn = "season";

    protected $fillable = [
        "villa_id",
        "season",
        "type",
        "status",
        "start_date",
        "end_date",
        "nightly_rate",
        "min_nights",
    ];

    protected $casts = [
            "villa_id" => "integer",
            "start_date" => "date",
            "end_date" => "date",
            "nightly_rate" => "decimal:2",
            "min_nights" => "integer",
        ];
    public function villa(): BelongsTo
    {
        return $this->belongsTo(Villa::class, "villa_id");
    }
}
