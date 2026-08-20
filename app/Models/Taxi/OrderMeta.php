<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMeta extends Model
{
  protected $table = 'order_metas';

  protected $fillable = [
    'id', 'order_id', 'meta_key', 'meta_value'
  ];


  public function order()
  {
    return $this->belongsTo(Order::class);
  }


}
