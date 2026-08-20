<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourBooking extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tour_bookings';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'tour_id',
        'schedule_id',
        'number_of_adults',
        'number_of_children',
        'base_price',
        'guide_id',
    ];

    /**
     * Get the booking that owns the tour booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    /**
     * Get the tour that owns the tour booking.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id', 'id');
    }

    /**
     * Get the schedule that owns the tour booking.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TourSchedule::class, 'schedule_id', 'id');
    }

    /**
     * Get the guide that owns the tour booking.
     */
    public function guide(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guide_id', 'id');
    }
}
