<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Relations\HasMany;
class ShopCategory extends BaseAdminModel
{
    protected $table = "admin_shop_categories";

    protected string $adminLabelColumn = "name";

    protected $fillable = [
        "name",
        "type",
        "status",
        "description",
    ];

    protected $casts = [];
    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class, "category_id");
    }
}
