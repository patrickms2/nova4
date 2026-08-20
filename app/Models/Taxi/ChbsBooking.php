<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChbsBooking extends Model
{
    use HasFactory;

  	protected $table = 'chbs_bookings';

    protected $fillable = [
        'order_id',
        'post_id',
        'post_author',
        'post_date',
        'post_modified',
        'post_title',
        'guid',
        'passenger_adult_number',
        'passenger_use_person_label',
        'booking_status_id',
        'booking_form_id',
        'pickup_time',
        'pickup_date',
        'pickup_datetime',
        'distance',
        'duration',
        'coordinate_pickup_lat',
        'coordinate_pickup_lng',
        'coordinate_pickup_address',
        'coordinate_dropoff_id',
        'coordinate_dropoff_name',
        'coordinate_dropoff_address',
        'coordinate_dropoff_lat',
        'coordinate_dropoff_lng',
        'price_distance_value',
        'price_distance_tax_rate_value',
        'client_first_name',
        'client_last_name',
        'client_email',
        'client_phone',
        'payment_id',
        'payment_name',
        'woocommerce_booking_id',
        'service_type_name',
        'transfer_type_name',
        'booking_status_name',
        'summary_duration',
        'summary_distance',
        'summary_value_net',
        'summary_value_gross',
        'summary_tax_sum',
        'summary_pay'
    ];
}
