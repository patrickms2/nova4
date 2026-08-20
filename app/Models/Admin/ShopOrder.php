<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\HasMany;
class ShopOrder extends BaseAdminModel
{
    protected $table = "admin_shop_orders";

    protected string $adminLabelColumn = "customer_name";

    protected $fillable = [
        "customer_name",
        "type",
        "status",
        "ordered_at",
        "total_amount",
        "payment_status",
        "email",
        "notes",
    ];

    protected $casts = [
            "ordered_at" => "datetime",
            "total_amount" => "decimal:2",
        ];
    public function lines(): HasMany
    {
        return $this->hasMany(ShopOrderLine::class, "order_id");
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, "payable_id")->where("payable_type", "shop_order");
    }
}
