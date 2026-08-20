<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'restaurants';

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
        'restaurant_name',
        'description',
        'location_id',
        'cuisine',
        'price_range',
        'base_price',
        'opening_time',
        'closing_time',
        'average_rating',
        'total_ratings',
        'main_image_url',
        'website',
        'phone',
        'email',
        'has_reservation',
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
        'base_price' => 'decimal:2',
        'opening_time' => 'datetime',
        'closing_time' => 'datetime',
        'average_rating' => 'decimal:2',
        'has_reservation' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Get the location that owns the restaurant.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    public function getNameAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['restaurant_name'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['restaurant_name'] = $value;
    }

    /**
     * Get the manager that owns the restaurant.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    /**
     * Get the images for the restaurant.
     */
    public function images(): HasMany
    {
        return $this->hasMany(RestaurantImage::class, 'restaurant_id', 'id');
    }

    /**
     * Get the menu categories for the restaurant.
     */
    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class, 'restaurant_id', 'id');
    }

    /**
     * Get the tables for the restaurant.
     */
    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class, 'restaurant_id', 'id');
    }

    /**
     * Get the bookings for the restaurant.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(RestaurantBooking::class, 'restaurant_id', 'id');
    }

    public function externalSyncMappings(): HasMany
    {
        return $this->hasMany(ExternalSyncMapping::class, 'target_id', 'id')
            ->where('target_model', 'restaurant');
    }
}
