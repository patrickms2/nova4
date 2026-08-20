<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\VillaBooking;

class Booking extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bookings';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_reference',
        'user_id',
        'booking_type',
        'booking_date',
        'status',
        'total_price',
        'discount_amount',
        'payment_status',
        'special_requests',
        'cancellation_reason',
        'last_updated',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_date' => 'datetime',
        'last_updated' => 'datetime',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the tour booking for the booking.
     */
    public function tourBooking(): HasOne
    {
        return $this->hasOne(TourBooking::class, 'booking_id', 'id');
    }

    /**
     * Get the hotel booking for the booking.
     */
    public function hotelBooking(): HasOne
    {
        return $this->hasOne(HotelBooking::class, 'booking_id', 'id');
    }
    /**
     * Get the hotel booking for the booking.
     */
    public function villaBooking(): HasOne
    {
        return $this->hasOne(VillaBooking::class, 'booking_id', 'id');
    }
    /**
     * Get the restaurant booking for the booking.
     */
    public function restaurantBooking(): HasOne
    {
        return $this->hasOne(RestaurantBooking::class, 'booking_id', 'id');
    }

    /**
     * Get the taxi booking for the booking.
     *
     * When a booking is deleted, the associated taxi booking should be deleted too.
     */
    public function taxiBooking(): HasOne
    {
        return $this->hasOne(TaxiBooking::class, 'booking_id', 'id');
    }

    /**
     * Get the package booking for the booking.
     */
    public function packageBooking(): HasOne
    {
        return $this->hasOne(PackageBooking::class, 'booking_id', 'id');
    }

    /**
     * Get the payments for the booking.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_id', 'id')->cascadeOnDelete();
    }

    /**
     * Get the ratings for the booking.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'booking_id', 'id')->cascadeOnDelete();
    }
}
