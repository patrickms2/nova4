<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
class Payment extends BaseAdminModel
{
    protected $table = "admin_payments";

    protected string $adminLabelColumn = "reference";

    protected $fillable = [
        "reference",
        "type",
        "status",
        "method",
        "amount",
        "paid_at",
        "payable_type",
        "payable_id",
        "notes",
    ];

    protected $casts = [
            "amount" => "decimal:2",
            "paid_at" => "datetime",
            "payable_id" => "integer",
        ];
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereIn($this->qualifyColumn("status"), ["paid", "verified", "completed"]);
    }

    public function scopeForPayable(Builder $query, string $type, int $id): Builder
    {
        return $query->where($this->qualifyColumn("payable_type"), $type)->where($this->qualifyColumn("payable_id"), $id);
    }
}
