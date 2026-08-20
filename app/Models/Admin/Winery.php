<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\HasMany;
class Winery extends BaseAdminModel
{
    protected $table = "admin_wineries";

    protected string $adminLabelColumn = "name";

    protected $fillable = [
        "name",
        "type",
        "status",
        "location",
        "capacity",
        "notes",
    ];

    protected $casts = [
            "capacity" => "integer",
        ];
    public function visits(): HasMany
    {
        return $this->hasMany(WineryVisit::class, "winery_id");
    }
}
