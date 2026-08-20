<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hotels';

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
        'name',
        'description',
        'location_id',
        'tariff_zone',
        'star_rating',
        'check_in_time',
        'check_out_time',
        'average_rating',
        'total_ratings',
        'main_image_url',
        'website',
        'phone',
        'email',
        'is_active',
        'is_featured',
        'manager_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'average_rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Get the location that owns the hotel.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    /**
     * Get the manager that owns the hotel.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    /**
     * Get the images for the hotel.
     */
    public function images(): HasMany
    {
        return $this->hasMany(HotelImage::class, 'hotel_id', 'id');
    }

    /**
     * Get the room types for the hotel.
     */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class, 'hotel_id', 'id');
    }

    /**
     * Get the amenities for the hotel.
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            HotelAmenity::class,
            'hotel_amenity_mapping',
            'hotel_id',
            'amenity_id',
            'hotel_id',
            'amenity_id'
        );
    }

    /**
     * Get the bookings for the hotel.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class, 'hotel_id', 'id');
    }

    public function externalSyncMappings(): HasMany
    {
        return $this->hasMany(ExternalSyncMapping::class, 'target_id', 'id')
            ->where('target_model', 'hotel');
    }
}
