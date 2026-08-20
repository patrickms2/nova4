<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Rental;
use App\Models\VillaAvailability;

class Villa extends BaseAdminModel
{
    protected $table = "villas";

    protected string $adminLabelColumn = "name";

    protected $fillable = [
        "name",
        "type",
        "status",
        "location",
        "capacity",
        "base_price",
        "notes",
    ];

    protected $casts = [
            "capacity" => "integer",
            "base_price" => "decimal:2",
        ];
    public function rates(): HasMany
    {
        return $this->hasMany(VillaRate::class, "villa_id");
    }

        public function availabilities()
    {
        return $this->hasMany(VillaAvailability::class, 'villa_id');
    }
    public function rentals()
    {
        return $this->hasMany(Rental::class, 'villa_id');
    }

    public function rentable()
    {
        return $this->morphTo();
    }
    public function reservations(): HasMany
    {
        return $this->hasMany(VillaReservation::class, "villa_id");
    }
}
