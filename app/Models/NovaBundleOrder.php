<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NovaBundleOrder extends Model
{
    protected $fillable = [
        'bundle_reference',
        'status',
        'customer_data',
        'la_geria_order_id',
        'la_geria_order_number',
        'la_geria_status',
        'la_geria_total',
        'lanzaloe_order_id',
        'lanzaloe_cart_id',
        'lanzaloe_status',
        'lanzaloe_error',
        'raw_result',
        'cancelled_at',
        'factura_id',
        'redsys_order',
        'payment_status',
        'payment_data',
        'paid_at',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    protected function casts(): array
    {
        return [
            'customer_data' => 'array',
            'raw_result' => 'array',
            'payment_data' => 'array',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
