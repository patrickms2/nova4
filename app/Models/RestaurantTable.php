<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'restaurant_tables';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'table_id';

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
        'restaurant_id',
        'table_number',
        'capacity',
        'location',
        'is_reservable',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_reservable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the restaurant that owns the table.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'restaurant_id');
    }

    /**
     * Get the bookings for the table.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(RestaurantBooking::class, 'table_id', 'table_id');
    }
}
