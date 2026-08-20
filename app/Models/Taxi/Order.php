<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Taxi\LineItem;
use App\Models\Taxi\TaxLine;
use App\Models\Taxi\OrderMeta;
use App\Models\Taxi\Booking;
use App\Models\Taxi\ChbsBooking;
use Codexshaper\WooCommerce\Facades\Order as WOrder;

class Order extends Model
{
  use HasFactory, SoftDeletes;

  protected $perPage = 20;

  protected $table = 'orders';

  protected $fillable = [
    'id', 'status','prices_include_tax',
    'date_created', 'date_modified', 'discount_total', 'discount_tax',
    'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax',
    'customer_id', 'first_name','last_name','name','order_key', 'payment_method', 'payment_method_title',
    'transaction_id', 'date_completed', 'date_paid', 'customer_ip_address',
    'first_name','last_name','billing_email', 'billing_phone'
  ];

    public function getTotalAmount()
    {
        return $this->total;
    }
  /**
   * Get the ChbsBooking associated with the Order
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function chbsBookings()
  {
    return $this->hasMany(ChbsBooking::class);
  }
  public function lineItems()
  {
    return $this->hasMany(LineItem::class);
  }

  public function taxLines()
  {
    return $this->hasMany(TaxLine::class);
  }

  public function orderMetas()
  {
    return $this->hasMany(OrderMeta::class);
  }

}
