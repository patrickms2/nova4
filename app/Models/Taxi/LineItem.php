<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineItem extends Model
{
    use HasFactory;

  	protected $table = 'line_items';

    protected $fillable = [
    'id', 'order_id', 'name', 'product_id', 'variation_id', 'quantity',
    'subtotal', 'subtotal_tax', 'total', 'total_tax', 'price'
  ];
}
