<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ShopOrderLine extends BaseAdminModel
{
    protected $table = "admin_shop_order_lines";

    protected string $adminLabelColumn = "notes";

    protected $fillable = [
        "order_id",
        "product_id",
        "type",
        "status",
        "quantity",
        "unit_price",
        "line_total",
        "notes",
    ];

    protected $casts = [
            "order_id" => "integer",
            "product_id" => "integer",
            "quantity" => "integer",
            "unit_price" => "decimal:2",
            "line_total" => "decimal:2",
        ];
    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, "order_id");
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, "product_id");
    }
}
