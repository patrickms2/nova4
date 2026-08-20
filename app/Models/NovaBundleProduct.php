<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NovaBundleProduct extends Model
{
    protected $fillable = [
        'name',
        'reference',
        'description',
        'status',
        'la_geria_product_id',
        'la_geria_product_name',
        'la_geria_quantity',
        'la_geria_unit_price',
        'lanzaloe_sku',
        'lanzaloe_product_name',
        'lanzaloe_quantity',
        'lanzaloe_unit_price',
        'total_price',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'la_geria_quantity' => 'integer',
            'la_geria_unit_price' => 'decimal:2',
            'lanzaloe_quantity' => 'integer',
            'lanzaloe_unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'status' => 'boolean',
        ];
    }
}
