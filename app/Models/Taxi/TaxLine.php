<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxLine extends Model
{
  protected $fillable = [
    'id', 'order_id', 'rate_code', 'rate_id', 'label',
    'tax_total', 'shipping_tax_total', 'rate_percent'
  ];
}
