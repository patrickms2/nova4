<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ShopProduct extends BaseAdminModel
{
    protected $table = "admin_shop_products";

    protected string $adminLabelColumn = "name";

    protected $fillable = [
        "category_id",
        "name",
        "type",
        "status",
        "sku",
        "stock",
        "price",
        "description",
    ];

    protected $casts = [
            "category_id" => "integer",
            "stock" => "integer",
            "price" => "decimal:2",
        ];
    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, "category_id");
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(ShopOrderLine::class, "product_id");
    }

    public function scopeLowStock(Builder $query, int $threshold = 5): Builder
    {
        return $query->where($this->qualifyColumn("stock"), "<=", $threshold);
    }
}
